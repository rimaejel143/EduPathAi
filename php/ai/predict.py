import sys
import json
import pickle
import traceback
import os
import mysql.connector

try:
    import numpy as np
except Exception:
    # numpy not available
    def respond(obj):
        print(json.dumps(obj))
        sys.stdout.flush()

    def _format_resp(obj):
        return {
            "success": bool(obj.get("success", False)),
            "major": str(obj.get("major", "")) if obj.get("major") is not None else "",
            "major_id": (
                int(obj.get("major_id")) if obj.get("major_id") is not None else None
            ),
            "confidence": (
                float(obj.get("confidence", 0.0))
                if obj.get("confidence") is not None
                else 0.0
            ),
            "error": obj.get("error") if obj.get("error") is not None else None,
            "trace": obj.get("trace") if obj.get("trace") is not None else None,
            "fallback": bool(obj.get("fallback", False)),
            "message": obj.get("message") if obj.get("message") is not None else None,
        }

    respond(_format_resp({"success": False, "message": "numpy not available"}))
    sys.exit(1)


def safe_int(x):
    try:
        return int(x)
    except Exception:
        return 0


def respond(obj):
    print(json.dumps(obj))
    sys.stdout.flush()


def _format_resp(obj):
    return {
        "success": bool(obj.get("success", False)),
        "major": str(obj.get("major", "")) if obj.get("major") is not None else "",
        "major_id": (
            int(obj.get("major_id")) if obj.get("major_id") is not None else None
        ),
        "confidence": (
            float(obj.get("confidence", 0.0))
            if obj.get("confidence") is not None
            else 0.0
        ),
        "error": obj.get("error") if obj.get("error") is not None else None,
        "trace": obj.get("trace") if obj.get("trace") is not None else None,
        "fallback": bool(obj.get("fallback", False)),
        "message": obj.get("message") if obj.get("message") is not None else None,
    }


try:
    # Expect 3 args
    if len(sys.argv) != 4:
        respond(_format_resp({"success": False, "message": "Missing parameters"}))
        sys.exit(1)

    s1 = safe_int(sys.argv[1])
    s2 = safe_int(sys.argv[2])
    s3 = safe_int(sys.argv[3])

    # load model, but provide a safe fallback predictor if model is missing
    model = None
    encoder = None
    data_model = None
    try:
        model_path = os.path.join(os.path.dirname(__file__), "model.pkl")
        with open(model_path, "rb") as f:
            data_model = pickle.load(f)
            model = data_model.get("model")
            encoder = data_model.get("encoder")
    except Exception as e:
        # We'll fall back to a simple heuristic predictor below
        model = None
        encoder = None

    X = np.array([[s1, s2, s3]])

    # If model is available, use it
    if model is not None and encoder is not None:
        try:
            pred = model.predict(X)[0]
            try:
                proba_arr = model.predict_proba(X)[0]
                proba = float(proba_arr.max())
            except Exception:
                proba = 0.0
            # Convert the encoded prediction back to the original label (major_id)
            try:
                if encoder is not None:
                    pred_label = encoder.inverse_transform([pred])[0]
                else:
                    pred_label = int(pred)
            except Exception:
                pred_label = int(pred)

            major_name = None
            try:
                # Query DB to get human-readable major_name for predicted major_id
                db_cfg = {
                    "host": "localhost",
                    "user": "root",
                    "password": "",
                    "database": "edupathdb",
                }
                conn = mysql.connector.connect(**db_cfg)
                cur = conn.cursor()
                cur.execute(
                    "SELECT major_name FROM majors WHERE major_id = %s",
                    (int(pred_label),),
                )
                r = cur.fetchone()
                if r:
                    major_name = r[0]
                cur.close()
                conn.close()
            except Exception:
                major_name = str(pred_label)

            result = _format_resp(
                {
                    "success": True,
                    "major": str(major_name),
                    "major_id": int(pred_label),
                    "confidence": round(proba * 100, 2),
                    "fallback": False,
                }
            )
            respond(result)
            sys.exit(0)
        except Exception as e:
            respond(
                _format_resp(
                    {
                        "success": False,
                        "message": "Prediction failed",
                        "error": str(e),
                        "trace": traceback.format_exc(),
                    }
                )
            )
            sys.exit(1)

    # Fallback heuristic predictor (when model.pkl missing or invalid)
    try:
        # Improved fallback heuristic predictor (when model.pkl missing or invalid)
        # Uses normalized percentages from the three parts to select a human-friendly major
        scores = [float(s1), float(s2), float(s3)]
        total = sum(scores)
        if total <= 0:
            # If no data, return a general recommendation
            major = "General Studies"
            confidence = 50.0
            major_id = None
        else:
            pa = (scores[0] / total) * 100.0
            pb = (scores[1] / total) * 100.0
            pc = (scores[2] / total) * 100.0

            # Rule-based selection with some realistic major names
            if pa >= pb and pa >= pc:
                # Logic/Skills-dominant
                if pa >= 60:
                    major = "Computer Science"
                elif pb >= 40:
                    major = "Data Science"
                else:
                    major = "Software Engineering"
                confidence = round(pa, 2)
            elif pb >= pa and pb >= pc:
                # Interests-dominant
                if pb >= 60:
                    major = "Graphic Design"
                elif pa >= 40:
                    major = "Business Analytics"
                else:
                    major = "Fine Arts"
                confidence = round(pb, 2)
            else:
                # Social/personality-dominant
                if pc >= 60:
                    major = "Psychology"
                elif pb >= 40:
                    major = "Communications"
                else:
                    major = "Education"
                confidence = round(pc, 2)

            # Try to resolve major_id from the majors table if available
            major_id = None
            try:
                db_cfg = {
                    "host": "localhost",
                    "user": "root",
                    "password": "",
                    "database": "edupathdb",
                }
                conn = mysql.connector.connect(**db_cfg)
                cur = conn.cursor()
                # First attempt exact match
                cur.execute(
                    "SELECT major_id FROM majors WHERE LOWER(major_name) = %s LIMIT 1",
                    (major.lower(),),
                )
                row = cur.fetchone()
                if row:
                    major_id = int(row[0])
                else:
                    # Try a LIKE match on main words
                    words = [w for w in major.split() if len(w) > 2]
                    if words:
                        like_clause = " OR ".join(["major_name LIKE %s" for _ in words])
                        params = [f"%{w}%" for w in words]
                        q = f"SELECT major_id FROM majors WHERE {like_clause} LIMIT 1"
                        cur.execute(q, params)
                        row = cur.fetchone()
                        if row:
                            major_id = int(row[0])
                cur.close()
                conn.close()
            except Exception:
                # ignore DB resolution failures — we'll just leave major_id as None
                major_id = None

        resp = {
            "success": True,
            "major": major,
            "major_id": major_id,
            "confidence": confidence,
            "fallback": True,
            "message": "Model file not found — returned rule-based prediction",
        }
        respond(_format_resp(resp))
        sys.exit(0)
    except Exception as e:
        respond(
            _format_resp(
                {
                    "success": False,
                    "message": "Fallback prediction failed",
                    "error": str(e),
                    "trace": traceback.format_exc(),
                }
            )
        )
        sys.exit(1)

except Exception as e:
    respond(
        _format_resp(
            {
                "success": False,
                "message": "Unhandled error",
                "error": str(e),
                "trace": traceback.format_exc(),
            }
        )
    )
    sys.exit(1)
