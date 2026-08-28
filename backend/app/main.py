from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware


from app.api.routers import auth, strategic, tasks, dashboard, employees, budget, risks, kpis
from app.api.routers import system_config, admin, location, departments as dept_routes
from app.ai.routers.ai_router import router as ai_router
from app.api.routers import dict_router 


app = FastAPI(
    title="AI Strategy Management System",
    description="نظام إدارة الاستراتيجية الحكومي المعتمد على الذكاء الاصطناعي - وزارة النفط والمعادن",
    version="2.0.0",
    docs_url="/docs",
    redoc_url="/redoc"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


app.include_router(auth.router)

app.include_router(strategic.router)

app.include_router(tasks.router)

app.include_router(dashboard.router)

app.include_router(employees.router)

app.include_router(budget.router)

app.include_router(risks.router)

app.include_router(kpis.router)

app.include_router(system_config.router)

app.include_router(admin.router)

app.include_router(location.router)

app.include_router(dept_routes.router)

app.include_router(dict_router.router)

app.include_router(ai_router)

@app.get("/")
async def root():
    return {
        "message": "AI Strategy Management System API",
        "version": "2.0.0",
        "status": "running",
        "docs": "/docs"
    }

@app.get("/health")
async def health_check():
    return {"status": "healthy"}

@app.on_event("startup")
async def startup_event():
    from app.ai.services.scheduler import ai_scheduler
    ai_scheduler.start()
    print("🚀 تم بدء جدولة AI التلقائية")
