#!/usr/bin/env python3
"""
AI Predictor: Takes 3 part scores and returns the recommended major.
Attempts to load a trained model; falls back to heuristic if not available.
"""
import sys
import pickle
import os
import json

MODEL_PATH = os.path.join(os.path.dirname(__file__), "model.pkl")
TARGET_MAX = 150.0


def normalize_input(scores):
    """
    Normalize scores to be within TARGET_MAX range.
    This matches the training data normalization.
    """
    vals = [float(s) for s in scores]
    m = max(vals) if vals else 0
    if m <= TARGET_MAX and m > 0:
        return vals
    if m <= 0:
        return [0.0, 0.0, 0.0]
    scale = TARGET_MAX / m
    return [float(v * scale) for v in vals]


def fallback_predict(scores):
    """
    Heuristic fallback when model.pkl is not available.
    Uses normalized weighted scores to predict major.
    """
    s = [float(max(0, v)) for v in scores]
    total = sum(s) if sum(s) > 0 else 1.0
    norm = [v / total for v in s]

    # Priority-based heuristic
    if norm[2] > 0.5:
        return {
            "major": "Data Science",
            "major_id": 3,
            "confidence": round(norm[2] * 100, 2),
        }
    if norm[0] > 0.5:
        return {
            "major": "Computer Science",
            "major_id": 1,
            "confidence": round(norm[0] * 100, 2),
        }
    if norm[1] > 0.5:
        return {
            "major": "Software Engineering",
            "major_id": 2,
            "confidence": round(norm[1] * 100, 2),
        }

    # If no single part dominates, pick the highest
    max_idx = max(range(len(norm)), key=lambda i: norm[i])
    picks = {
        0: ("Computer Science", 1),
        1: ("Software Engineering", 2),
        2: ("Data Science", 3),
    }
    major, major_id = picks.get(max_idx, ("Computer Science", 1))
    return {
        "major": major,
        "major_id": major_id,
        "confidence": round(norm[max_idx] * 100, 2),
    }


def predict(scores):
    """Return prediction dict for given scores (does not print).
    scores: iterable of 3 numeric values
    Returns: dict with keys: major, major_id, confidence
    """
    try:
        s_in = [float(x) for x in scores][:3]
    except Exception:
        s_in = [0.0, 0.0, 0.0]

    scores_norm = normalize_input(s_in)

    # Try to load trained model
    if os.path.exists(MODEL_PATH):
        try:
            with open(MODEL_PATH, "rb") as f:
                data = pickle.load(f)
            model = data.get("model")
            encoder = data.get("encoder")

            if model is None or encoder is None:
                raise Exception("Model or encoder missing from pickle")

            X = [scores_norm]
            pred_enc = model.predict(X)

            # Get human-readable label
            try:
                label = encoder.inverse_transform(pred_enc)[0]
            except Exception:
                label = str(pred_enc[0])

            # Get confidence
            try:
                proba_arr = model.predict_proba(X)[0]
                confidence = float(max(proba_arr) * 100.0)
                # compute margin between top two probabilities (percentage points)
                sorted_idx = sorted(
                    range(len(proba_arr)), key=lambda i: proba_arr[i], reverse=True
                )
                top = proba_arr[sorted_idx[0]]
                second = proba_arr[sorted_idx[1]] if len(sorted_idx) > 1 else 0.0
                margin_pct = (top - second) * 100.0
            except Exception:
                confidence = 90.0
                margin_pct = 100.0

            # Map major name to major_id
            static_map = {
                "Computer Science": 1,
                "Software Engineering": 2,
                "Data Science": 3,
                "Medicine": 4,
                "Biotechnology": 5,
                "Pharmacy": 6,
                "Business Administration": 7,
                "Finance": 8,
                "Marketing": 9,
                "Graphic Design": 10,
                "Architecture": 11,
                "Psychology": 12,
            }
            major_id = static_map.get(str(label), 0)

            return {
                "major": str(label),
                "major_id": int(major_id),
                "confidence": round(float(confidence), 2),
                "close_call": True if (margin_pct < 5.0) else False,
            }

        except Exception:
            # fall back to heuristic
            pass

    # fallback heuristic
    return fallback_predict(scores_norm)


def main():
    args = sys.argv[1:]
    try:
        scores = [float(a) for a in args[:3]]
    except Exception:
        scores = [0.0, 0.0, 0.0]

    # Normalize scores
    scores = normalize_input(scores)

    # Try to load trained model
    if os.path.exists(MODEL_PATH):
        try:
            with open(MODEL_PATH, "rb") as f:
                data = pickle.load(f)
            model = data.get("model")
            encoder = data.get("encoder")

            if model is None or encoder is None:
                raise Exception("Model or encoder missing from pickle")

            X = [scores]
            pred_enc = model.predict(X)

            # Get human-readable label
            try:
                label = encoder.inverse_transform(pred_enc)[0]
            except Exception:
                label = str(pred_enc[0])

            # Get confidence
            try:
                proba_arr = model.predict_proba(X)[0]
                confidence = float(max(proba_arr) * 100.0)
                sorted_idx = sorted(
                    range(len(proba_arr)), key=lambda i: proba_arr[i], reverse=True
                )
                top = proba_arr[sorted_idx[0]]
                second = proba_arr[sorted_idx[1]] if len(sorted_idx) > 1 else 0.0
                margin_pct = (top - second) * 100.0
            except Exception:
                confidence = 90.0
                margin_pct = 100.0

            # Map major name to major_id
            static_map = {
                "Computer Science": 1,
                "Software Engineering": 2,
                "Data Science": 3,
                "Medicine": 4,
                "Biotechnology": 5,
                "Pharmacy": 6,
                "Business Administration": 7,
                "Finance": 8,
                "Marketing": 9,
                "Graphic Design": 10,
                "Architecture": 11,
                "Psychology": 12,
            }
            major_id = static_map.get(str(label), 0)

            out = {
                "major": str(label),
                "major_id": int(major_id),
                "confidence": round(float(confidence), 2),
                "close_call": True if (margin_pct < 5.0) else False,
            }
            print(json.dumps(out))
            return

        except Exception as e:
            # Log error but fall through to fallback
            sys.stderr.write(f"Model load error: {str(e)}\n")

    # Use fallback heuristic
    result = fallback_predict(scores)
    print(json.dumps(result))


if __name__ == "__main__":
    main()
