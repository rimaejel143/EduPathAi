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
            "major_id": int(obj.get("major_id")) if obj.get("major_id") is not None else None,
            "confidence": float(obj.get("confidence", 0.0)) if obj.get("confidence") is not None else 0.0,
            "error": obj.get("error") if obj.get("error") is not None else None,
            "trace": obj.get("trace") if obj.get("trace") is not None else None,
            "fallback": bool(obj.get("fallback", False)),
            "message": obj.get("message") if obj.get("message") is not None else None
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
        "major_id": int(obj.get("major_id")) if obj.get("major_id") is not None else None,
        "confidence": float(obj.get("confidence", 0.0)) if obj.get("confidence") is not None else 0.0,
        "error": obj.get("error") if obj.get("error") is not None else None,
        "trace": obj.get("trace") if obj.get("trace") is not None else None,
        "fallback": bool(obj.get("fallback", False)),
        "message": obj.get("message") if obj.get("message") is not None else None
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
                db_cfg = {"host": "localhost", "user": "root", "password": "", "database": "edupathdb"}
                conn = mysql.connector.connect(**db_cfg)
                cur = conn.cursor()
                cur.execute("SELECT major_name FROM majors WHERE major_id = %s", (int(pred_label),))
                r = cur.fetchone()
                if r:
                    major_name = r[0]
                cur.close()
                conn.close()
            except Exception:
                major_name = str(pred_label)

            result = _format_resp({
                "success": True,
                "major": str(major_name),
                "major_id": int(pred_label),
                "confidence": round(proba * 100, 2),
                "fallback": False
            })
            respond(result)
            sys.exit(0)
        except Exception as e:
            respond(_format_resp({"success": False, "message": "Prediction failed", "error": str(e), "trace": traceback.format_exc()}))
            sys.exit(1)

    # Fallback heuristic predictor (when model.pkl missing or invalid)
    try:
        # Choose category by highest score among the three parts
        scores = [s1, s2, s3]
        max_idx = int(np.argmax(scores))
        # Map index to a safe human-readable major name
        fallback_majors = [
            "Analytical Studies",
            "Applied Sciences",
            "Creative Arts"
        ]
        major = fallback_majors[max_idx] if max_idx < len(fallback_majors) else "General Studies"

        total = float(sum(scores))
        if total <= 0:
            confidence = 50.0
        else:
            confidence = round((float(scores[max_idx]) / total) * 100.0, 2)

        respond(_format_resp({
            "success": True,
            "major": major,
            "confidence": confidence,
            "fallback": True,
            "message": "Model file not found — returned heuristic prediction"
        }))
        sys.exit(0)
    except Exception as e:
        respond(_format_resp({"success": False, "message": "Fallback prediction failed", "error": str(e), "trace": traceback.format_exc()}))
        sys.exit(1)

except Exception as e:
    respond(_format_resp({"success": False, "message": "Unhandled error", "error": str(e), "trace": traceback.format_exc()}))
    sys.exit(1)
