# Crypto Wallet Module

Модуль учёта крипто-баланса пользователя на Laravel.

## Что реализовано

- Зачисление средств с ожиданием подтверждений блокчейна
- Списание с заморозкой баланса до подтверждения
- Защита от дублирования транзакций через idempotency key
- Race condition защита через `lockForUpdate()`
- Комиссия 1% при выводе
- Разное количество подтверждений для разных сетей (ERC20, TRC20, BEP20)
- Асинхронная обработка через Queue/Jobs

## Установка

```bash
php artisan migrate
php artisan queue:work --queue=crypto
```

## API

| Метод | URL | Описание |
|-------|-----|----------|
| GET | /api/wallet | Баланс пользователя |
| POST | /api/wallet/deposit | Зачисление |
| POST | /api/wallet/withdraw | Вывод |

## Пример запроса deposit

```json
{
  "amount": "100.5",
  "currency": "USDT",
  "network": "TRC20",
  "tx_hash": "abc123...64chars",
  "from_address": "TXxx...",
  "to_address": "TYxx..."
}
```

## Пример запроса withdraw

```json
{
  "amount": "50",
  "currency": "USDT",
  "network": "TRC20",
  "to_address": "TXxx..."
}
```
