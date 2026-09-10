#!/usr/bin/env python3
"""Local ZPL print bridge for the RCLSMO conference dashboard.

Runs only on 127.0.0.1:5030. The dashboard sends already generated ZPL here,
and this process forwards it RAW to a local USB printer queue or directly to a
network Zebra/ZPL-compatible printer on TCP/9100.

macOS USB printer (current TSC TE200 queue):
  python3 zpl_print_bridge.py

Specific macOS printer queue:
  ZPL_PRINTER_NAME=TSC_TE200 python3 zpl_print_bridge.py

Windows USB/default printer:
  pip install pywin32
  python zpl_print_bridge.py

Specific Windows printer:
  set ZPL_PRINTER_NAME=ZDesigner ZD421-203dpi ZPL
  python zpl_print_bridge.py

Network printer:
  set ZPL_PRINTER_IP=192.168.1.50
  python zpl_print_bridge.py
"""

from __future__ import annotations

import json
import os
import socket
import subprocess
import sys
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

HOST = "127.0.0.1"
PORT = 5030
ALLOWED_ORIGINS = {"https://rclsmo.ru", "https://www.rclsmo.ru"}
MAX_BODY = 128 * 1024
DEFAULT_MAC_PRINTER = "TSC_TE200"


def configured_printer_name() -> str:
    printer_name = os.environ.get("ZPL_PRINTER_NAME", "").strip()
    if printer_name:
        return printer_name
    if sys.platform == "darwin":
        return DEFAULT_MAC_PRINTER
    return ""


def printer_target() -> str:
    printer_ip = os.environ.get("ZPL_PRINTER_IP", "").strip()
    if printer_ip:
        return f"{printer_ip}:9100"

    printer_name = configured_printer_name()
    if printer_name:
        return printer_name

    if os.name == "nt":
        try:
            import win32print  # type: ignore

            return win32print.GetDefaultPrinter()
        except Exception:
            return "Windows default printer (pywin32 required)"

    return "not configured"


def print_via_macos_cups(data: bytes, printer_name: str) -> str:
    try:
        result = subprocess.run(
            ["lp", "-d", printer_name, "-o", "raw"],
            input=data,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            timeout=10,
            check=False,
        )
    except FileNotFoundError as exc:
        raise RuntimeError("macOS CUPS command 'lp' is not available") from exc
    except subprocess.TimeoutExpired as exc:
        raise RuntimeError("CUPS print command timed out") from exc

    if result.returncode != 0:
        error = result.stderr.decode("utf-8", errors="replace").strip()
        raise RuntimeError(error or f"CUPS lp failed with exit code {result.returncode}")

    return printer_name


def print_zpl(zpl: str) -> str:
    data = zpl.encode("utf-8")
    printer_ip = os.environ.get("ZPL_PRINTER_IP", "").strip()
    if printer_ip:
        with socket.create_connection((printer_ip, 9100), timeout=4) as sock:
            sock.sendall(data)
        return f"{printer_ip}:9100"

    if sys.platform == "darwin":
        return print_via_macos_cups(data, configured_printer_name() or DEFAULT_MAC_PRINTER)

    if os.name != "nt":
        raise RuntimeError("Set ZPL_PRINTER_IP, use macOS CUPS, or run the bridge on Windows")

    try:
        import win32print  # type: ignore
    except ImportError as exc:
        raise RuntimeError("pywin32 is not installed: run 'pip install pywin32'") from exc

    printer_name = configured_printer_name() or win32print.GetDefaultPrinter()
    handle = win32print.OpenPrinter(printer_name)
    try:
        job = win32print.StartDocPrinter(handle, 1, ("RCLSMO badge", None, "RAW"))
        try:
            win32print.StartPagePrinter(handle)
            try:
                win32print.WritePrinter(handle, data)
            finally:
                win32print.EndPagePrinter(handle)
        finally:
            win32print.EndDocPrinter(handle)
    finally:
        win32print.ClosePrinter(handle)

    return printer_name


class Handler(BaseHTTPRequestHandler):
    server_version = "RCLSMO-ZPL-Bridge/1.1"

    def log_message(self, fmt: str, *args: object) -> None:
        print("[print-bridge] " + (fmt % args))

    def _origin_allowed(self) -> bool:
        origin = self.headers.get("Origin", "")
        return origin == "" or origin in ALLOWED_ORIGINS

    def _cors(self) -> None:
        origin = self.headers.get("Origin", "")
        if origin in ALLOWED_ORIGINS:
            self.send_header("Access-Control-Allow-Origin", origin)
            self.send_header("Vary", "Origin")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        self.send_header("Access-Control-Allow-Private-Network", "true")
        self.send_header("Cache-Control", "no-store")

    def _json(self, status: int, payload: dict) -> None:
        body = json.dumps(payload, ensure_ascii=False).encode("utf-8")
        self.send_response(status)
        self._cors()
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_OPTIONS(self) -> None:  # noqa: N802
        if not self._origin_allowed():
            self._json(403, {"status": "error", "message": "origin_not_allowed"})
            return
        self.send_response(204)
        self._cors()
        self.end_headers()

    def do_GET(self) -> None:  # noqa: N802
        if not self._origin_allowed():
            self._json(403, {"status": "error", "message": "origin_not_allowed"})
            return
        if self.path != "/health":
            self._json(404, {"status": "error", "message": "not_found"})
            return
        self._json(200, {"status": "success", "printer": printer_target()})

    def do_POST(self) -> None:  # noqa: N802
        if not self._origin_allowed():
            self._json(403, {"status": "error", "message": "origin_not_allowed"})
            return
        if self.path != "/print":
            self._json(404, {"status": "error", "message": "not_found"})
            return

        try:
            length = int(self.headers.get("Content-Length", "0"))
        except ValueError:
            length = 0
        if length <= 0 or length > MAX_BODY:
            self._json(413, {"status": "error", "message": "invalid_body_size"})
            return

        try:
            payload = json.loads(self.rfile.read(length).decode("utf-8"))
            zpl = str(payload.get("zpl", ""))
        except Exception:
            self._json(400, {"status": "error", "message": "invalid_json"})
            return

        if not zpl.startswith("^XA") or not zpl.rstrip().endswith("^XZ"):
            self._json(422, {"status": "error", "message": "invalid_zpl"})
            return

        try:
            target = print_zpl(zpl)
            self._json(200, {"status": "success", "printer": target})
        except Exception as exc:
            print(f"[print-bridge] print error: {exc}", file=sys.stderr)
            self._json(503, {"status": "error", "message": str(exc)})


if __name__ == "__main__":
    print(f"RCLSMO ZPL print bridge: http://{HOST}:{PORT}")
    print(f"Printer: {printer_target()}")
    print("Keep this window open during check-in.")
    ThreadingHTTPServer((HOST, PORT), Handler).serve_forever()
