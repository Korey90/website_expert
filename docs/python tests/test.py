import time
import secrets
from uuid import uuid4

import requests

URL = "https://formsubmit.co/ajax/kontakt@tellsell.pl"

REQUEST_COUNT = 100  # Zakres 1–5
DELAY_SECONDS = 5

FIRST_NAMES = (
    "Anna",
    "Bartek",
    "Celina",
    "Damian",
    "Ewa",
    "Filip",
    "Julia",
    "Kamil",
    "Laura",
    "Marek",
)

LAST_NAMES = (
    "Kowalska",
    "Nowak",
    "Wisznievski",
    "Zielinska",
    "Kaczmarek",
    "Wojcik",
    "Lewandowska",
    "Dabrowski",
)

COMPANY_NAMES = (
    "Northwind Studio",
    "Bright Pixel Lab",
    "Blue Oak Digital",
    "Silver Fern Works",
    "Orbit Craft",
    "Mosaic Point",
)

TOPICS = (
    "Outsourcing sprzedaży",
    "Budowa działów sprzedaży",
    "Audyt sprzedaży",
    "Rekrutacja handlowców",
    "Szkolenia sprzedażowe",
    "Procesy sprzedaży",
    "Consulting sprzedażowy",
    "Interim Sales Management",
    "Wdrożenia AI w sprzedaży",
    "Inny temat",
)


def random_test_payload(number: int) -> dict[str, str]:
    """Build a unique, realistic-looking payload without using real PII."""
    first_name = secrets.choice(FIRST_NAMES)
    last_name = secrets.choice(LAST_NAMES)
    topic = secrets.choice(TOPICS)
    unique_id = f"{number}-{uuid4().hex[:10]}"

    return {
        "_subject": topic,
        "_captcha": "false",
        "_template": "table",
        "Wiadomość": "bardzo prosze o wiecej informacji na temat waszych usług. ",
        "Imię": f"{first_name} {last_name}",
        "E-mail": f"{first_name.lower()}-{unique_id}@gmail.com",
        "Numer telefonu": f"000{secrets.randbelow(1_000_000):06d}",
        "Nazwa firmy": f"{secrets.choice(COMPANY_NAMES)} {number}",
        "Temat rozmowy": topic,
        "Zgoda RODO": "on",
    }

headers = {
    "Accept": "application/json",
    "User-Agent": "TellSell/1.0",
    "Origin": "https://tellsell.pl",
    "Referer": "https://tellsell.pl/",
}

if not 1 <= REQUEST_COUNT <= 100:
    raise ValueError("REQUEST_COUNT musi mieścić się w zakresie 1–100.")

with requests.Session() as session:
    for number in range(1, REQUEST_COUNT + 1):
        current_payload = random_test_payload(number)

        try:
            response = session.post(
                URL,
                data=current_payload,
                headers=headers,
                timeout=15,
            )

            print(
                f"[{number}/{REQUEST_COUNT}] "
                f"HTTP {response.status_code}: {response.text[:300]}"
            )

            if response.status_code == 429:
                print("Serwer zastosował rate limiting. Test został zatrzymany.")
                break

            if '"success":"false"' in response.text:
                print("Formularz nie jest aktywny (brak aktywacji). Test został zatrzymany.")
                break

        except requests.RequestException as error:
            print(f"[{number}/{REQUEST_COUNT}] Błąd: {error}")

        if number < REQUEST_COUNT:
            time.sleep(DELAY_SECONDS)