from fastapi import FastAPI
from databases import Database
from contextlib import asynccontextmanager
import logging

# Corrected MySQL connection string
DATABASE_URL = "mysql+aiomysql://root:pTT!CT01@10.1.0.47:3306/testing_db"

logger = logging.getLogger("uvicorn")

database = Database(DATABASE_URL)

@asynccontextmanager
async def lifespan(app: FastAPI):
    try:
        await database.connect()
        await database.execute("SELECT 1")  # Simple test query
        logger.info("✅ Successfully connected to MySQL database")
    except Exception as e:
        logger.error(f"❌ Failed to connect to MySQL database: {e}")
        raise  # Re-raise to prevent app startup on failure

    yield  # Allows the application to run while the database is connected

    await database.disconnect()
    logger.info("🔴 Database connection closed")

app = FastAPI(lifespan=lifespan)

@app.get("/db-check")
async def db_check():
    try:
        await database.execute("SELECT 1")
        return {"status": "success", "message": "Database is connected!"}
    except Exception as e:
        return {"status": "error", "message": str(e)}