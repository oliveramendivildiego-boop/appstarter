from router import ask

while True:
    q = input("\n🔎 Pregunta: ")
    if q.lower() in ["exit", "quit"]:
        break

    res = ask(q)
    print("\n📌 Respuesta:\n")
    print(res)