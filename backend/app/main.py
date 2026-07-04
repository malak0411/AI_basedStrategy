from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.api.routers import auth, strategic, tasks, dashboard, employees, budget, risks, kpis, ai
from app.api.routers import system_config, admin, location

app = FastAPI(
    title="AI Strategy Management System",
    description="نظام إدارة الاستراتيجية الحكومي المعتمد على الذكاء الاصطناعي",
    version="1.0.0"
)

# CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# تسجيل الـ Routers
app.include_router(auth.router)
app.include_router(strategic.router)
app.include_router(tasks.router)
app.include_router(dashboard.router)
app.include_router(employees.router)
app.include_router(budget.router)
app.include_router(risks.router)
app.include_router(kpis.router)
app.include_router(ai.router)
app.include_router(system_config.router)
app.include_router(admin.router)
app.include_router(location.router)

@app.get("/")
async def root():
    return {"message": "AI Strategy Management System API", "version": "1.0.0", "status": "running"}
