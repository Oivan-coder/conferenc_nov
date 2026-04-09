#!/usr/bin/env python3
from __future__ import annotations

import json
import re
from dataclasses import dataclass
from datetime import datetime
from html import unescape
from pathlib import Path
from typing import Iterable


ROOT = Path(__file__).resolve().parent.parent
REPORTS_DIR = ROOT / "reports"
MANIFEST_PATH = REPORTS_DIR / "reports.json"
SITEMAP_PATH = ROOT / "sitemap.xml"
REPORTS_PAGE_PATH = ROOT / "reports.html"
SITE_URL = "https://rclsmo.ru"


META_PATTERNS = {
    "title": re.compile(r'<meta\s+name=["\']report-title["\']\s+content=["\'](.*?)["\']', re.IGNORECASE | re.DOTALL),
    "period": re.compile(r'<meta\s+name=["\']report-period["\']\s+content=["\'](.*?)["\']', re.IGNORECASE | re.DOTALL),
    "description": re.compile(r'<meta\s+name=["\']report-description["\']\s+content=["\'](.*?)["\']', re.IGNORECASE | re.DOTALL),
    "publish_date": re.compile(r'<meta\s+name=["\']report-publish-date["\']\s+content=["\'](.*?)["\']', re.IGNORECASE | re.DOTALL),
    "badge": re.compile(r'<meta\s+name=["\']report-badge["\']\s+content=["\'](.*?)["\']', re.IGNORECASE | re.DOTALL),
    "tags": re.compile(r'<meta\s+name=["\']report-tags["\']\s+content=["\'](.*?)["\']', re.IGNORECASE | re.DOTALL),
}


@dataclass
class ReportMeta:
    title: str
    period: str
    description: str
    publish_date: str
    sort_date: str
    year: int
    tags: list[str]
    badge: str
    file: str
    featured: bool = False


def read_text(path: Path) -> str:
    return path.read_text(encoding="utf-8", errors="ignore")


def cleanup_text(value: str) -> str:
    value = re.sub(r"<[^>]+>", " ", value)
    value = unescape(value)
    value = re.sub(r"\s+", " ", value)
    return value.strip()


def find_first(pattern: re.Pattern[str], text: str) -> str:
    match = pattern.search(text)
    return cleanup_text(match.group(1)) if match else ""


def parse_title(html: str, fallback: str) -> str:
    meta_title = find_first(META_PATTERNS["title"], html)
    if meta_title:
        return meta_title

    title = find_first(re.compile(r"<title>(.*?)</title>", re.IGNORECASE | re.DOTALL), html)
    if title:
        return title

    heading = find_first(re.compile(r"<h1[^>]*>(.*?)</h1>", re.IGNORECASE | re.DOTALL), html)
    return heading or fallback


def parse_description(html: str) -> str:
    meta_description = find_first(META_PATTERNS["description"], html)
    if meta_description:
        return meta_description

    description = find_first(re.compile(r'<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']', re.IGNORECASE | re.DOTALL), html)
    if description:
        return description

    paragraph = find_first(re.compile(r"<p[^>]*>(.*?)</p>", re.IGNORECASE | re.DOTALL), html)
    return paragraph[:220].rstrip(".") + "." if paragraph else "Управленческий отчет по проекту."


def parse_period(html: str, sort_date: str) -> str:
    meta_period = find_first(META_PATTERNS["period"], html)
    if meta_period:
        return meta_period

    explicit = find_first(re.compile(r"(Период:\s*[^<\n]+)", re.IGNORECASE), html)
    if explicit:
        return explicit

    if sort_date:
        return f"Публикация: {format_display_date(sort_date)}"
    return "Период уточняется"


def parse_tags(html: str) -> list[str]:
    meta_tags = find_first(META_PATTERNS["tags"], html)
    if meta_tags:
        return [tag.strip() for tag in meta_tags.split(",") if tag.strip()]
    return ["еженедельный", "отчет"]


def parse_iso_date(value: str) -> str:
    value = value.strip()
    for fmt in ("%Y-%m-%d", "%d.%m.%Y", "%d.%m.%y"):
        try:
            return datetime.strptime(value, fmt).strftime("%Y-%m-%d")
        except ValueError:
            continue
    return ""


def parse_publish_date(html: str, filename: str) -> str:
    meta_date = find_first(META_PATTERNS["publish_date"], html)
    if meta_date:
        parsed = parse_iso_date(meta_date)
        if parsed:
            return parsed

    filename_iso = re.search(r"(20\d{2}-\d{2}-\d{2})", filename)
    if filename_iso:
        return filename_iso.group(1)

    dot_date = re.search(r"(\d{2}\.\d{2}\.\d{4})", html + " " + filename)
    if dot_date:
        parsed = parse_iso_date(dot_date.group(1))
        if parsed:
            return parsed

    return ""


def format_display_date(iso_date: str) -> str:
    if not iso_date:
        return ""
    return datetime.strptime(iso_date, "%Y-%m-%d").strftime("%d.%m.%Y")


def collect_reports(paths: Iterable[Path]) -> list[ReportMeta]:
    reports: list[ReportMeta] = []
    for path in paths:
        if path.name.startswith("_"):
            continue

        html = read_text(path)
        sort_date = parse_publish_date(html, path.name)
        title = parse_title(html, path.stem.replace("-", " ").replace("_", " ").strip())
        description = parse_description(html)
        period = parse_period(html, sort_date)
        tags = parse_tags(html)
        year = int(sort_date[:4]) if sort_date else datetime.fromtimestamp(path.stat().st_mtime).year
        badge = find_first(META_PATTERNS["badge"], html)

        reports.append(
            ReportMeta(
                title=title,
                period=period,
                description=description,
                publish_date=format_display_date(sort_date),
                sort_date=sort_date or datetime.fromtimestamp(path.stat().st_mtime).strftime("%Y-%m-%d"),
                year=year,
                tags=tags,
                badge=badge,
                file=f"reports/{path.name}",
            )
        )

    reports.sort(key=lambda item: item.sort_date, reverse=True)
    for index, report in enumerate(reports):
        report.featured = index == 0
        if not report.badge:
            report.badge = "Свежий" if index == 0 else "Архив"
    return reports


def write_manifest(reports: list[ReportMeta]) -> None:
    payload = build_payload(reports)
    MANIFEST_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def build_payload(reports: list[ReportMeta]) -> list[dict]:
    return [
        {
            "title": report.title,
            "period": report.period,
            "description": report.description,
            "file": report.file,
            "publishDate": report.publish_date,
            "sortDate": report.sort_date,
            "badge": report.badge,
            "year": report.year,
            "tags": report.tags,
            "featured": report.featured,
        }
        for report in reports
    ]


def update_reports_page(reports: list[ReportMeta]) -> None:
    if not REPORTS_PAGE_PATH.exists():
        return

    content = REPORTS_PAGE_PATH.read_text(encoding="utf-8")
    payload = json.dumps(build_payload(reports), ensure_ascii=False, indent=2)
    updated = re.sub(
        r'(<script id="reportsData" type="application/json">\s*)(.*?)(\s*</script>)',
        rf"\1{payload}\3",
        content,
        flags=re.DOTALL,
    )
    REPORTS_PAGE_PATH.write_text(updated, encoding="utf-8")


def build_sitemap_entries(reports: list[ReportMeta]) -> str:
    entries = [
        "  <url>",
        f"    <loc>{SITE_URL}/reports.html</loc>",
        f"    <lastmod>{datetime.now().strftime('%Y-%m-%d')}</lastmod>",
        "    <changefreq>weekly</changefreq>",
        "    <priority>0.8</priority>",
        "  </url>",
    ]

    for report in reports:
        entries.extend(
            [
                "  <url>",
                f"    <loc>{SITE_URL}/{report.file}</loc>",
                f"    <lastmod>{report.sort_date}</lastmod>",
                "    <changefreq>weekly</changefreq>",
                "    <priority>0.6</priority>",
                "  </url>",
            ]
        )

    return "\n".join(entries)


def update_sitemap(reports: list[ReportMeta]) -> None:
    if not SITEMAP_PATH.exists():
        return

    content = SITEMAP_PATH.read_text(encoding="utf-8")
    filtered = re.sub(
        r"\s*<url>\s*<loc>https://rclsmo\.ru/(reports\.html|reports/[^<]+)</loc>.*?</url>",
        "",
        content,
        flags=re.DOTALL,
    ).rstrip()
    filtered = filtered.replace("</urlset>", build_sitemap_entries(reports) + "\n</urlset>")
    SITEMAP_PATH.write_text(filtered + "\n", encoding="utf-8")


def main() -> None:
    REPORTS_DIR.mkdir(exist_ok=True)
    report_files = sorted(REPORTS_DIR.glob("*.html"))
    reports = collect_reports(report_files)
    write_manifest(reports)
    update_reports_page(reports)
    update_sitemap(reports)
    print(f"Generated manifest for {len(reports)} report(s).")


if __name__ == "__main__":
    main()
