from query import ask

while True:
    q = input("\n🔎 Pregunta: ")

    if q.lower() in ["exit", "quit"]:
        break

    print("\n🤖 Respuesta:\n")
    print(ask(q))