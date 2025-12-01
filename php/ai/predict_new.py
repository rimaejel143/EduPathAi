#!/usr/bin/env python3
import sys
import pickle
import os
import json

MODEL_PATH = os.path.join(os.path.dirname(__file__), "model.pkl")
TARGET_MAX = 150.0


def normalize_input(scores):
    # Ensure same normalization as training: if any value > TARGET_MAX, scale row down
    vals = [float(s) for s in scores]
    m = max(vals)
    if m <= TARGET_MAX and m > 0:
        return [float(v) for v in vals]
    if m <= 0:
        return [0.0, 0.0, 0.0]
    scale = TARGET_MAX / m
    return [float(v * scale) for v in vals]


def main(argv):
    args = argv[1:]
    try:
        scores = [float(a) for a in args[:3]]
    except Exception:
        scores = [0.0, 0.0, 0.0]

    scores = normalize_input(scores)

    if os.path.exists(MODEL_PATH):
        try:
            with open(MODEL_PATH, "rb") as f:
                data = pickle.load(f)
        except Exception as e:
            print(
                json.dumps(
                    {
                        "success": False,
                        "message": "Failed loading model",
                        "error": str(e),
                    }
                )
            )
            return

        model = data.get("model")
        encoder = data.get("encoder")
        X = [scores]
        try:
            pred_enc = model.predict(X)
            label = encoder.inverse_transform(pred_enc)[0]
        except Exception:
            label = str(pred_enc[0])

        try:
            proba_arr = model.predict_proba(X)[0]
            confidence = float(max(proba_arr) * 100.0)
        except Exception:
            confidence = 0.0

        # Try to map to numeric id using static map
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
            "success": True,
        }
        print(json.dumps(out))
    else:
        # fallback heuristic
        s = [float(max(0, v)) for v in scores]
        total = sum(s) if sum(s) > 0 else 1.0
        norm = [v / total for v in s]
        if norm[2] > 0.5:
            major = "Data Science"
            conf = norm[2] * 100
        elif norm[0] > 0.5:
            major = "Computer Science"
            conf = norm[0] * 100
        elif norm[1] > 0.5:
            major = "Software Engineering"
            conf = norm[1] * 100
        else:
            idx = max(range(len(norm)), key=lambda i: norm[i])
            major = {
                0: "Computer Science",
                1: "Software Engineering",
                2: "Data Science",
            }.get(idx, "Computer Science")
            conf = norm[idx] * 100
        print(
            json.dumps(
                {
                    "major": major,
                    "major_id": 0,
                    "confidence": round(float(conf), 2),
                    "success": True,
                }
            )
        )


if __name__ == "__main__":
    main(sys.argv)
import sys
import pickle
import os
import json

try:
    import mysql.connector
except Exception:
    mysql = None

MODEL_PATH = os.path.join(os.path.dirname(__file__), "model.pkl")


def _resolve_major_id(db_conn, major_name):
    """Try to resolve a human major name to numeric major_id using DB. Returns int or None."""
    if db_conn is None:
        return None
    try:
        cur = db_conn.cursor()
        cur.execute(
            "SELECT major_id FROM majors WHERE LOWER(major_name)=%s LIMIT 1",
            (major_name.lower(),),
        )
        r = cur.fetchone()
        if r:
            return int(r[0])
        words = [w for w in major_name.split() if len(w) > 2]
        if words:
            like_clause = " OR ".join(["LOWER(major_name) LIKE %s" for _ in words])
            params = [f"%{w.lower()}%" for w in words]
            q = f"SELECT major_id FROM majors WHERE {like_clause} LIMIT 1"
            cur.execute(q, params)
            r = cur.fetchone()
            if r:
                return int(r[0])
    except Exception:
        pass
    return None


def fallback_predict(scores):
    s = [float(max(0, v)) for v in scores]
    total = sum(s) if sum(s) > 0 else 1.0
    norm = [v / total for v in s]
    if norm[2] > 0.5:
        return {
            "major": "Data Science",
            "major_id": None,
            "confidence": round(norm[2] * 100, 2),
        }
    if norm[0] > 0.5:
        return {
            "major": "Computer Science",
            "major_id": None,
            "confidence": round(norm[0] * 100, 2),
        }
    if norm[1] > 0.5:
        return {
            "major": "Software Engineering",
            "major_id": None,
            "confidence": round(norm[1] * 100, 2),
        }
    max_idx = max(range(len(norm)), key=lambda i: norm[i])
    pick = {0: "Computer Science", 1: "Software Engineering", 2: "Data Science"}.get(
        max_idx, "Computer Science"
    )
    return {
        "major": pick,
        "major_id": None,
        "confidence": round(norm[max_idx] * 100, 2),
    }


def main():
    args = sys.argv[1:]
    try:
        scores = [float(a) for a in args[:3]]
    except Exception:
        scores = [0.0, 0.0, 0.0]

    db_conn = None
    if mysql is not None:
        try:
            db_cfg = {
                "host": "localhost",
                "user": "root",
                "password": "",
                "database": "edupathdb",
            }
            db_conn = mysql.connector.connect(**db_cfg)
        except Exception:
            db_conn = None

    if os.path.exists(MODEL_PATH):
        try:
            with open(MODEL_PATH, "rb") as f:
                data = pickle.load(f)
            model = data.get("model")
            encoder = data.get("encoder")
            X = [scores]
            pred_enc = model.predict(X)
            try:
                label = encoder.inverse_transform(pred_enc)[0]
            except Exception:
                label = str(pred_enc[0])

            try:
                proba_arr = model.predict_proba(X)[0]
                confidence = float(max(proba_arr) * 100.0)
            except Exception:
                confidence = 90.0

            major_id = _resolve_major_id(db_conn, str(label))
            if major_id is None:
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
                major_id = static_map.get(str(label), None)

            # Ensure major_id is numeric (or 0 fallback)
            if major_id is None:
                major_id = 0

            out = {
                "major": str(label),
                "major_id": int(major_id),
                "confidence": round(float(confidence), 2),
            }
            print(json.dumps(out))
        except Exception as e:
            print(
                json.dumps(
                    {
                        "major": "Error",
                        "major_id": 0,
                        "confidence": 0.0,
                        "error": str(e),
                    }
                )
            )
    else:
        # fallback returns major_id as 0 when unknown
        res = fallback_predict(scores)
        if res.get("major_id") is None:
            res["major_id"] = 0
        print(json.dumps(res))


if __name__ == "__main__":
    main()
import sys
import pickle
import os
import json

MODEL_PATH = os.path.join(os.path.dirname(__file__), "model.pkl")


def fallback_predict(scores):
    # A slightly smarter fallback: normalized weighted heuristic
    s = [float(max(0, v)) for v in scores]
    total = sum(s) if sum(s) > 0 else 1.0
    norm = [v / total for v in s]
    if norm[2] > 0.5:
        return {
            "major": "Data Science",
            "major_id": None,
            "confidence": int(norm[2] * 100),
        }
    if norm[0] > 0.5:
        import sys
        import pickle
        import os
        import json

        try:
            import mysql.connector
        except Exception:
            mysql = None

        MODEL_PATH = os.path.join(os.path.dirname(__file__), "model.pkl")

        def _resolve_major_id(db_conn, major_name):
            """Try to resolve a human major name to numeric major_id using DB. Returns int or None."""
            if db_conn is None:
                return None
            try:
                cur = db_conn.cursor()
                # exact lower-case match
                cur.execute(
                    "SELECT major_id FROM majors WHERE LOWER(major_name)=%s LIMIT 1",
                    (major_name.lower(),),
                )
                r = cur.fetchone()
                if r:
                    return int(r[0])
                # try token LIKE matches
                words = [w for w in major_name.split() if len(w) > 2]
                if words:
                    like_clause = " OR ".join(
                        ["LOWER(major_name) LIKE %s" for _ in words]
                    )
                    params = [f"%{w.lower()}%" for w in words]
                    q = f"SELECT major_id FROM majors WHERE {like_clause} LIMIT 1"
                    cur.execute(q, params)
                    r = cur.fetchone()
                    if r:
                        return int(r[0])
            except Exception:
                pass
            return None

        def fallback_predict(scores):
            s = [float(max(0, v)) for v in scores]
            total = sum(s) if sum(s) > 0 else 1.0
            norm = [v / total for v in s]
            if norm[2] > 0.5:
                return {
                    "major": "Data Science",
                    "major_id": None,
                    "confidence": round(norm[2] * 100, 2),
                }
            if norm[0] > 0.5:
                return {
                    "major": "Computer Science",
                    "major_id": None,
                    "confidence": round(norm[0] * 100, 2),
                }
            if norm[1] > 0.5:
                return {
                    "major": "Software Engineering",
                    "major_id": None,
                    "confidence": round(norm[1] * 100, 2),
                }
            max_idx = max(range(len(norm)), key=lambda i: norm[i])
            pick = {
                0: "Computer Science",
                1: "Software Engineering",
                2: "Data Science",
            }.get(max_idx, "Computer Science")
            return {
                "major": pick,
                "major_id": None,
                "confidence": round(norm[max_idx] * 100, 2),
            }

        def main():
            args = sys.argv[1:]
            try:
                scores = [float(a) for a in args[:3]]
            except Exception:
                scores = [0.0, 0.0, 0.0]

            db_conn = None
            if mysql is not None:
                try:
                    db_cfg = {
                        "host": "localhost",
                        "user": "root",
                        "password": "",
                        "database": "edupathdb",
                    }
                    db_conn = mysql.connector.connect(**db_cfg)
                except Exception:
                    db_conn = None

            # If model exists, predict and compute probability
            if os.path.exists(MODEL_PATH):
                try:
                    with open(MODEL_PATH, "rb") as f:
                        data = pickle.load(f)
                    model = data.get("model")
                    encoder = data.get("encoder")
                    X = [scores]
                    pred_enc = model.predict(X)
                    # get human-readable label
                    try:
                        label = encoder.inverse_transform(pred_enc)[0]
                    except Exception:
                        label = str(pred_enc[0])

                    # probability
                    confidence = None
                    try:
                        proba_arr = model.predict_proba(X)[0]
                        confidence = float(max(proba_arr) * 100.0)
                    except Exception:
                        confidence = 90.0

                    # resolve major_id via DB if possible
                    major_id = _resolve_major_id(db_conn, str(label))
                    # fallback static mapping if DB not available
                    if major_id is None:
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
                        major_id = static_map.get(str(label), None)

                    out = {
                        "major": str(label),
                        "major_id": int(major_id) if major_id is not None else None,
                        "confidence": round(float(confidence), 2),
                    }
                    print(json.dumps(out))
                except Exception as e:
                    # if any error, return fallback heuristic result
                    print(
                        json.dumps(
                            {
                                "major": "Error",
                                "major_id": None,
                                "confidence": 0.0,
                                "error": str(e),
                            }
                        )
                    )
            else:
                print(json.dumps(fallback_predict(scores)))

        if __name__ == "__main__":
            main()
