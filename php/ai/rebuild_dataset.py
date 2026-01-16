#!/usr/bin/env python3
"""
Rebuild and balance php/ai/dataset.csv so each of the 12 majors has exactly
TARGET_PER_MAJOR rows. Downsample when a major has too many rows; upsample
(with replacement) when it has too few.

Usage:
  python rebuild_dataset.py [TARGET_PER_MAJOR]

This script writes a backup of the original dataset to `dataset_backup_{ts}.csv`
and replaces `dataset.csv` with the balanced version.
"""
import csv
import os
import sys
import random
from collections import defaultdict
from datetime import datetime

ROOT = os.path.dirname(__file__)
DATA_CSV = os.path.join(ROOT, "dataset.csv")
BACKUP_FMT = os.path.join(ROOT, "dataset_backup_{ts}.csv")

MAJORS = [
    "Computer Science",
    "Software Engineering",
    "Data Science",
    "Medicine",
    "Biotechnology",
    "Pharmacy",
    "Business Administration",
    "Finance",
    "Marketing",
    "Graphic Design",
    "Architecture",
    "Psychology",
]


def read_dataset(path):
    rows = []
    if not os.path.exists(path):
        return rows
    with open(path, newline="", encoding="utf-8") as f:
        rdr = csv.reader(f)
        header = None
        for r in rdr:
            if not header:
                header = r
                # accept files with/without header
                if header and header[0].lower().startswith("part"):
                    continue
            if len(r) >= 4:
                # p1, p2, p3, major
                rows.append({"p1": r[0], "p2": r[1], "p3": r[2], "major": r[3]})
    return rows


def write_dataset(path, rows):
    with open(path, "w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        w.writerow(["part1_score", "part2_score", "part3_score", "major_name"])
        for r in rows:
            w.writerow([r["p1"], r["p2"], r["p3"], r["major"]])


def balance_dataset(rows, target_per_major):
    groups = defaultdict(list)
    for r in rows:
        m = r["major"].strip()
        if m in MAJORS:
            groups[m].append(r)
        else:
            # ignore unknown labels
            pass

    # Ensure every major present in groups
    for m in MAJORS:
        groups.setdefault(m, [])

    balanced = []
    for m in MAJORS:
        items = groups[m]
        if len(items) >= target_per_major:
            # downsample without replacement
            selected = random.sample(items, target_per_major)
        else:
            # upsample with replacement
            selected = list(items)
            while len(selected) < target_per_major:
                if items:
                    selected.append(random.choice(items))
                else:
                    # if no rows exist for this major at all, create a synthetic
                    # duplicated empty row with zeros — user asked for logical duplication
                    selected.append({"p1": "0", "p2": "0", "p3": "0", "major": m})
        balanced.extend(selected[:target_per_major])

    # Shuffle final dataset to mix majors
    random.shuffle(balanced)
    return balanced


def main():
    target = 20
    if len(sys.argv) >= 2:
        try:
            target = int(sys.argv[1])
        except Exception:
            pass

    print(f"Reading dataset from {DATA_CSV}")
    rows = read_dataset(DATA_CSV)
    print(f"Total rows found: {len(rows)}")

    # backup
    ts = datetime.utcnow().strftime("%Y%m%dT%H%M%SZ")
    backup = BACKUP_FMT.format(ts=ts)
    if os.path.exists(DATA_CSV):
        print(f"Writing backup to {backup}")
        os.rename(DATA_CSV, backup)

    balanced = balance_dataset(rows, target)

    print(f"Writing balanced dataset ({len(balanced)} rows), {target} per major")
    write_dataset(DATA_CSV, balanced)
    print("Done.")


if __name__ == "__main__":
    main()
