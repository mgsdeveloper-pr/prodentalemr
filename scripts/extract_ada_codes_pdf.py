import argparse
import json
import re
from pathlib import Path

import pdfplumber


CODE_PATTERN = re.compile(r"^(D\d{4})(-)?$")


def normalize_text(value: str) -> str:
    value = value.replace("\ufffd", "-")
    value = value.replace("\u2013", "-").replace("\u2014", "-")
    value = re.sub(r"\s+", " ", value).strip()
    value = re.sub(r"\s*/\s*", "/", value)
    return value.replace("Orthodont ia", "Orthodontia")


def column_text(words: list[dict], minimum_x: float, maximum_x: float | None = None) -> str:
    selected = [
        word
        for word in words
        if word["x0"] >= minimum_x
        and (maximum_x is None or word["x0"] < maximum_x)
    ]
    selected.sort(key=lambda word: (round(word["top"], 1), word["x0"]))
    return normalize_text(" ".join(word["text"] for word in selected))


def horizontal_boundaries(page, x_position: float) -> list[float]:
    boundaries: list[float] = []

    for edge in page.horizontal_edges:
        if edge["x0"] <= x_position <= edge["x1"]:
            top = round(float(edge["top"]), 1)

            if not boundaries or abs(boundaries[-1] - top) > 1:
                boundaries.append(top)

    return sorted(boundaries)


def enclosing_bounds(boundaries: list[float], top: float, page_height: float) -> tuple[float, float]:
    previous = max((boundary for boundary in boundaries if boundary <= top), default=top - 1)
    following = min((boundary for boundary in boundaries if boundary > top), default=page_height - 57)
    return previous + 0.5, following - 0.5


def extract_codes(pdf_path: Path) -> list[dict]:
    rows: dict[str, dict] = {}

    with pdfplumber.open(pdf_path) as pdf:
        for page_number, page in enumerate(pdf.pages, start=1):
            words = page.extract_words(use_text_flow=False, keep_blank_chars=False)
            code_words = []

            for word in words:
                match = CODE_PATTERN.fullmatch(word["text"])

                if word["x0"] < 90 and match:
                    code_words.append({**word, "code": match.group(1), "deleted": bool(match.group(2))})

            code_words.sort(key=lambda word: word["top"])
            class_boundaries = horizontal_boundaries(page, 520)
            description_boundaries = horizontal_boundaries(page, 150)

            for index, code_word in enumerate(code_words):
                row_top = code_word["top"] - 1
                description_bottom = (
                    code_words[index + 1]["top"] - 1
                    if index + 1 < len(code_words)
                    else enclosing_bounds(description_boundaries, code_word["top"], page.height)[1]
                )
                description_words = [
                    word
                    for word in words
                    if row_top <= word["top"] < description_bottom
                ]
                class_top, class_bottom = enclosing_bounds(
                    class_boundaries,
                    code_word["top"],
                    page.height,
                )
                class_words = [
                    word
                    for word in words
                    if class_top <= word["top"] < class_bottom
                ]

                code = code_word["code"]
                description = column_text(description_words, 90, 250)
                procedure_class = column_text(class_words, 485)

                if not description or not procedure_class:
                    continue

                rows[code] = {
                    "procedure_code": code,
                    "description": description,
                    "class": procedure_class,
                    "is_active": not code_word["deleted"],
                    "source_page": page_number,
                }

    return [rows[code] for code in sorted(rows)]


def main() -> None:
    parser = argparse.ArgumentParser(description="Extract ADA procedure codes from a tabular PDF.")
    parser.add_argument("pdf", type=Path)
    parser.add_argument("output", type=Path)
    args = parser.parse_args()

    rows = extract_codes(args.pdf)
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(rows, indent=2, ensure_ascii=True), encoding="utf-8")
    print(f"Extracted {len(rows)} ADA procedure codes to {args.output}")


if __name__ == "__main__":
    main()
