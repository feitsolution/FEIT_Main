######################################
# File here is for PROD DO NOT TOUCH #
######################################

import logging

from flask import Flask
from waitress import serve

from adms_wrapper.routers.dashboard_router import router as dashboard_router
from adms_wrapper.routers.employee_router import router as employee_router
from adms_wrapper.routers.mappings_router import router as mappings_router
from adms_wrapper.routers.reports_router import router as reports_router
from adms_wrapper.routers.settings_router import router as settings_router

app = Flask(__name__)
app.secret_key = 'secret_key'
app.config["MAX_CONTENT_LENGTH"] = 16 * 1024 * 1024
app.config["UPLOAD_FOLDER"] = "uploads"

app.register_blueprint(dashboard_router)
app.register_blueprint(employee_router)
app.register_blueprint(mappings_router)
app.register_blueprint(reports_router)
app.register_blueprint(settings_router)

logger = logging.getLogger(__name__)

if __name__ == "__main__":
    logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
    logger.setLevel(logging.INFO)

    logger.info('\033[93mStarting server...\033[0m')
    serve(
        app,
        host="0.0.0.0",
        port=5000,
        threads=6,
        connection_limit=1000,
        cleanup_interval=30,
        channel_timeout=120
    )
