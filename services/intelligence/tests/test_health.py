import sys
import unittest
from pathlib import Path


SOURCE_ROOT = Path(__file__).resolve().parents[1] / "src"
sys.path.insert(0, str(SOURCE_ROOT))

from fastapi.testclient import TestClient  # noqa: E402

from k8_canvas.main import app  # noqa: E402


class HealthContractTests(unittest.TestCase):
    def test_health_contract_is_explicit_and_versioned(self) -> None:
        response = TestClient(app).get("/health")
        result = response.json()

        self.assertEqual(response.status_code, 200)
        self.assertEqual(result["service"], "k8-canvas-intelligence")
        self.assertEqual(result["status"], "ok")
        self.assertEqual(result["phase"], "foundation")
        self.assertRegex(result["version"], r"^0\.1\.0-alpha\.1$")


if __name__ == "__main__":
    unittest.main()
