from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from .config import settings
from .api.routers import auth, strategic, tasks, dashboard, employees, budget, risks, kpis, ai

app = FastAPI(
    title="AI-Based Strategy Management System",
    description="نظام إدارة الاستراتيجية الحكومي المعتمد على الذكاء الاصطناعي",
    version="1.0.0",
    docs_url="/api/docs",
    redoc_url="/api/redoc"
)

# CORS - السماح لـ Laravel بالاتصال
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:3000", "http://127.0.0.1:3000"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# تسجيل الرواترز
app.include_router(auth.router)
app.include_router(strategic.router)
app.include_router(tasks.router)
app.include_router(dashboard.router)
app.include_router(employees.router)
app.include_router(budget.router)
app.include_router(risks.router)
app.include_router(kpis.router)
app.include_router(ai.router)

@app.get("/")
async def root():
    return {"message": "AI-Based Strategy Management System", "status": "running"}

@app.get("/health")
async def health():
    return {"status": "healthy", "version": "1.0.0"}
