from router import ask

def main():
    while True:
        query = input("\n🔎 Pregunta: ")

        if query in ["exit", "quit"]:
            break

        response = ask(query)

        print("\n📌 Respuesta:\n")
        print(response)


if __name__ == "__main__":
    main()