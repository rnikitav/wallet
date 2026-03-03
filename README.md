# Crypto Wallet Module

Модуль учёта крипто-баланса пользователя на Laravel.

## Что реализовано

- Зачисление средств с ожиданием подтверждений блокчейна
- Списание с заморозкой баланса до подтверждения
- Защита от дублирования транзакций через idempotency key
- Race condition защита через `lockForUpdate()`
- Комиссия 1% при выводе
- Разное количество подтверждений для разных сетей (ERC20, TRC20, BEP20, BTC)
- Асинхронная обработка через Queue/Jobs

## Установка

```bash
php artisan migrate
php artisan serve
composer install
php artisan queue:work --queue=crypto
```

## API

| Метод | URL | Auth | Описание |
|-------|-----|------|----------|
| POST | /api/register | — | Регистрация, возвращает токен |
| POST | /api/login | — | Логин, возвращает токен |
| GET | /api/wallet | Bearer | Баланс пользователя |
| POST | /api/wallet/deposit | Bearer | Зачисление |
| POST | /api/wallet/withdraw | Bearer | Вывод |

---

## Флоу пополнения и вывода

### Депозит

```
POST /api/wallet/deposit
        │
        ▼
  WalletService::deposit()
  ├─ проверка дубликата (idempotency_key по tx_hash)
  ├─ создаёт транзакцию status=pending, confirmations=0
  └─ диспатчит джобу с задержкой 30 сек
        │
        ▼ (через 30 сек)
  ProcessCryptoTransaction::handle()
  ├─ fetchConfirmations() → current + rand(3,7)
  ├─ сохраняет новое значение confirmations
  │
  ├─ [confirmations < required] → повторный dispatch через 1 мин
  │         └─ повторяется пока не наберётся нужное кол-во
  │
  └─ [confirmations >= required] → WalletService::confirm()
            ├─ wallet.balance += amount
            └─ transaction.status = confirmed
```

Баланс не изменяется пока транзакция `pending` — деньги зачисляются только после финального `confirm()`.

### Вывод

```
POST /api/wallet/withdraw
        │
        ▼
  WalletService::withdraw()
  ├─ проверяет available_balance >= amount + fee
  ├─ frozen_balance += (amount + fee)   ← деньги заморожены сразу
  ├─ создаёт транзакцию status=pending
  └─ диспатчит джобу
        │
        ▼
  ProcessCryptoTransaction::handle()
  └─ [подтверждено] → WalletService::confirm()
            ├─ balance -= (amount + fee)
            ├─ frozen_balance -= (amount + fee)
            └─ transaction.status = confirmed
```

Комиссия 1% от суммы вывода.

### Количество подтверждений по сетям

| Сеть  | Нужно подтверждений | Итераций (~мин) |
|-------|--------------------:|----------------:|
| ERC20 | 12                  | 2–4             |
| TRC20 | 20                  | 3–7             |
| BEP20 | 15                  | 3–5             |
| BTC   | 6                   | 1–2             |

---

## Curl-запросы

### Регистрация

```bash
curl -s -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123"}' \
  | jq .
```

Ответ:
```json
{
  "token": "1|abc123..."
}
```

### Логин

```bash
curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"test@example.com","password":"password123"}' \
  | jq .
```

### Баланс

```bash
curl -s http://localhost:8000/api/wallet \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Accept: application/json" \
  | jq .
```

Ответ:
```json
{
  "data": [
    {
      "currency": "USDT",
      "balance": "100.000000000000000000",
      "frozen_balance": "0.000000000000000000",
      "available_balance": "100.000000000000000000"
    }
  ]
}
```

### Депозит

```bash
curl -s -X POST http://localhost:8000/api/wallet/deposit \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "amount": "100.00",
    "currency": "USDT",
    "network": "TRC20",
    "tx_hash": "a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2",
    "from_address": "TXYZabc123sender",
    "to_address": "TXYZabc123receiver"
  }' \
  | jq .
```

Ответ:
```json
{
  "data": {
    "id": 1,
    "type": "deposit",
    "status": "pending",
    "amount": "100.000000000000000000",
    "fee": "0.000000000000000000",
    "network": "TRC20"
  },
  "message": "Deposit pending confirmation."
}
```

### Вывод

```bash
curl -s -X POST http://localhost:8000/api/wallet/withdraw \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "amount": "50.00",
    "currency": "USDT",
    "network": "TRC20",
    "to_address": "TXYZabc123receiver"
  }' \
  | jq .
```

Ответ:
```json
{
  "data": {
    "id": 2,
    "type": "withdrawal",
    "status": "pending",
    "amount": "50.000000000000000000",
    "fee": "0.500000000000000000",
    "network": "TRC20"
  },
  "message": "Withdrawal pending processing."
}
```