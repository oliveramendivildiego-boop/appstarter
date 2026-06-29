from models import get_llm

llm = get_llm()

respuesta = llm.complete(
    "Di solamente: Hola Diego"
)

print(respuesta)