# Heseya Shop System 2.0

## Przygotowanie projektu
```
composer i
npm i
npm run dev
```

Do js i scss alternatywnie
```
npm run prod
```
lub
```
npm run watch
```

Skopiuj `.env.example` do `.env`.

Wygeneruj klucz aplikacji i odpal migracje z seederem.
```
php artisan key:generate
php artisan migrate --seed
```

Seeder utworzy urzytkownika `admin@example.com` z hasłem `secret`.

Jak dostajesz błąd 403 prawdopodobnie nie masz uprawnień. Mozna je ustawić pod linkiem `/admin/settings/users`.

Jak by coś nie działało związanego z cache (np. routing).
```
php artisan optimize
```

## Przygotowanie projektu w Docker
Skopiuj `.env.example` do `.env`.

Utwórz środowisko
```
docker-compose up
```

Uruchamianie środowiska nie przypinająć go do konsoli (lub z aplikacji)
```
docker-compose up -d
```

Zatrzymywanie środowiska (lub z aplikacji)
```
docker-compose stop
```

Wejście do kontenera (lub z aplikacji)
```
docker exec -it depth(lub inna nazwa katalogu projektu)_app_1 bash
```

## Styl kodu
Ustaw twoje IDE, zeby korzystało z pliku .editorconfig. W VS Code jest na to dodatek.

Pisząc kod do tego projektu stosuj się do wszystkich zasad z (https://github.com/maciejjeziorski/laravel-best-practices-pl).

Dodatkowo:
- wszystkie pliki związane tylko z panelem trzymamy w osobnych folderach, tak zeby dało się je usunąc łatwo jak juz przejdziemy na Vue,
- przy walidacji uzywaj stringów `'required|max:20'` zamiast tablic,
- odwołania do autoryzacji przy uzyciu `Auth::` zamiast `auth()`.
