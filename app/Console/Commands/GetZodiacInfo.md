# Команда zodiac:info

Команда для получения информации о знаке зодиака и дополнительных данных.

## Использование

```bash
php artisan zodiac:info <input>
```

## Параметры

- `input` - может быть одним из следующих форматов:
  - Дата в формате MM-DD (например: 03-15)
  - Дата в формате YYYY-MM-DD (например: 1990-07-20)
  - Имя именинника (например: "John Doe")
  - Telegram username (например: "@johndoe")

## Примеры использования

### По дате в формате MM-DD
```bash
php artisan zodiac:info 03-15
```

### По полной дате
```bash
php artisan zodiac:info 1990-07-20
```

### По имени или username
```bash
php artisan zodiac:info "John Doe"
php artisan zodiac:info "@johndoe"
```

## Вывод

Команда выводит:
- Знак зодиака для указанной даты
- Если указан конкретный год (не 9996), дополнительно выводит:
  - День недели
  - Год какого символа по китайскому календарю
  - Фазу луны

## Пример вывода

```
Zodiac Information:
Date: 1990-07-20
Zodiac Sign: Cancer

Additional Information:
Day of Week: Friday
Chinese Zodiac: Horse
Moon Phase: New Moon
``` 