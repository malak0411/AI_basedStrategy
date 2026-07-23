"""
Ollama Client - نموذج Qwen 2.5 محلي
"""
import ollama

class OllamaClient:
    def __init__(self, model: str = "qwen2.5:7b"):
        self.model = model
        # اختبار الاتصال
        try:
            ollama.list()
            print(f"✅ Ollama Client جاهز (نموذج: {self.model})")
        except Exception as e:
            print(f"⚠️ تأكد من تشغيل Ollama: {str(e)}")

    def generate(self, prompt: str, system_instruction: str = None) -> str:
        messages = []
        if system_instruction:
            messages.append({"role": "system", "content": system_instruction})
        messages.append({"role": "user", "content": prompt})

        response = ollama.chat(
            model=self.model,
            messages=messages
        )
        return response['message']['content']

ollama_client = OllamaClient()
