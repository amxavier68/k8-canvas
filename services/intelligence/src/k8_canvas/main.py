"""HTTP boundary for approved K8 Canvas intelligence capabilities."""

from fastapi import FastAPI

from k8_canvas import __version__

app = FastAPI(
    title="K8 Canvas Intelligence",
    version=__version__,
    docs_url=None,
    redoc_url=None,
)


@app.get("/health", tags=["operations"])
def health() -> dict[str, str]:
    """Return the stable foundation health contract."""
    return {
        "service": "k8-canvas-intelligence",
        "status": "ok",
        "version": __version__,
        "phase": "foundation",
    }
