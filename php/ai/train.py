import csv
import os
import pickle
from collections import defaultdict
import random
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import LabelEncoder

DATASET_PATH = os.path.join(os.path.dirname(__file__), "dataset.csv")
MODEL_PATH = os.path.join(os.path.dirname(__file__), "model.pkl")
TARGET_MAX = 150.0


def load_raw(path):
    rows = []
    if not os.path.exists(path):
        return rows
    with open(path, newline="", encoding="utf-8") as f:
        reader = csv.DictReader(f)
        for row in reader:
            try:
                p1 = float(row.get("part1_score") or row.get("part1") or 0)
            except Exception:
                p1 = 0.0
            try:
                p2 = float(row.get("part2_score") or row.get("part2") or 0)
            except Exception:
                p2 = 0.0
            try:
                p3 = float(row.get("part3_score") or row.get("part3") or 0)
            except Exception:
                p3 = 0.0
            major = (
                row.get("major_name") or row.get("major") or row.get("label") or ""
            ).strip()
            if not major:
                continue
            rows.append((p1, p2, p3, major))
    return rows


def normalize_row(p1, p2, p3):
    # If any part exceeds TARGET_MAX, scale the entire row down so max == TARGET_MAX
    vals = [float(p1), float(p2), float(p3)]
    m = max(vals)
    if m <= TARGET_MAX and m > 0:
        return [round(v, 6) for v in vals]
    if m <= 0:
        return [0.0, 0.0, 0.0]
    scale = TARGET_MAX / m
    return [round(v * scale, 6) for v in vals]


def build_balanced_dataset(rows, target_per_major=60, seed=42):
    random.seed(seed)
    by_major = defaultdict(list)
    for p1, p2, p3, major in rows:
        # skip absurd outliers where any value > 10000
        if max(abs(p1), abs(p2), abs(p3)) > 10000:
            continue
        norm = normalize_row(p1, p2, p3)
        by_major[major].append(norm)

    # compute means
    means = {}
    for major, vecs in by_major.items():
        if not vecs:
            means[major] = [50.0, 50.0, 50.0]
            continue
        m1 = sum(v[0] for v in vecs) / len(vecs)
        m2 = sum(v[1] for v in vecs) / len(vecs)
        m3 = sum(v[2] for v in vecs) / len(vecs)
        means[major] = [m1, m2, m3]

    cleaned_X = []
    cleaned_y = []

    # For each major, upsample or jitter to reach target_per_major
    for major, vecs in by_major.items():
        # deduplicate
        unique = []
        seen = set()
        for v in vecs:
            key = tuple(round(x, 3) for x in v)
            if key in seen:
                continue
            seen.add(key)
            unique.append(v)

        # keep existing unique rows
        for v in unique:
            cleaned_X.append([float(v[0]), float(v[1]), float(v[2])])
            cleaned_y.append(major)

        # generate synthetic rows if needed
        needed = max(0, target_per_major - len(unique))
        mean = means.get(major, [50.0, 50.0, 50.0])
        for i in range(needed):
            # small gaussian jitter around mean
            jitter = [
                max(
                    0.0,
                    min(TARGET_MAX, random.gauss(mean[0], max(3.0, mean[0] * 0.05))),
                ),
                max(
                    0.0,
                    min(TARGET_MAX, random.gauss(mean[1], max(3.0, mean[1] * 0.05))),
                ),
                max(
                    0.0,
                    min(TARGET_MAX, random.gauss(mean[2], max(3.0, mean[2] * 0.05))),
                ),
            ]
            cleaned_X.append([round(x, 6) for x in jitter])
            cleaned_y.append(major)

    return cleaned_X, cleaned_y


def train_model():
    rows = load_raw(DATASET_PATH)
    if not rows:
        print("No dataset.csv found or empty — aborting training")
        return

    X, y = build_balanced_dataset(rows, target_per_major=60)

    # If still small, fall back to synthetic centers
    if len(X) < 50:
        print("Insufficient cleaned data, falling back to synthetic centers")
        centers = {
            "Computer Science": [130, 110, 60],
            "Software Engineering": [95, 150, 75],
            "Data Science": [70, 90, 170],
            "Medicine": [55, 45, 185],
            "Biotechnology": [50, 60, 170],
            "Pharmacy": [40, 50, 160],
            "Business Administration": [75, 100, 80],
            "Finance": [90, 120, 60],
            "Marketing": [80, 70, 50],
            "Graphic Design": [50, 90, 40],
            "Architecture": [70, 80, 60],
            "Psychology": [60, 70, 130],
        }
        for major, center in centers.items():
            for i in range(40):
                jitter = [
                    max(0.0, min(TARGET_MAX, c + random.gauss(0, 6))) for c in center
                ]
                X.append(jitter)
                y.append(major)

    # Encode labels
    le = LabelEncoder()
    y_enc = le.fit_transform(y)

    # Train with balanced class weights
    model = RandomForestClassifier(
        n_estimators=300, class_weight="balanced", random_state=42
    )
    model.fit(X, y_enc)

    # Save model and encoder
    with open(MODEL_PATH, "wb") as f:
        pickle.dump({"model": model, "encoder": le}, f)

    print(f"MODEL_TRAINED_SUCCESSFULLY Saved to: {MODEL_PATH}")


if __name__ == "__main__":
    train_model()
