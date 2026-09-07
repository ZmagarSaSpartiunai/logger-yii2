# Logger API (Yii2)

A small JSON API that writes a log message to one channel or fans it out to every
configured channel, reporting per-channel delivery. Built on Yii 2.0 as a
counterpart to the same service implemented on Laravel.

The transports themselves are deliberately stubs — each channel writes through
Yii's logger instead of really sending mail or inserting a row. The point of the
exercise is the composition around them.

## Endpoints

One resource, one verb:

| Method | Path        | Purpose             |
|--------|-------------|---------------------|
| POST   | `/api/logs` | Write a log message |

Request body:

```json
{ "message": "Disk is full.", "channels": ["file"], "level": "warning" }
```

Both extra fields are optional:

- `channels` — omit it for the configured default, list the ones you want
  (`email`, `file`, `database`), or pass `["*"]` for every configured channel.
- `level` — `debug`, `info`, `warning` or `error`; defaults to `info`.

Writing a log changes state and is not idempotent, so the endpoint only accepts
POST. "Send to every channel" is a value of `channels`, not a second endpoint:
a URI names a resource, not an operation.

Response:

```json
{
  "data": {
    "deliveries": [{ "channel": "email", "delivered": true }],
    "level": "info",
    "logged_at": "2026-09-07T10:28:02+00:00"
  }
}
```

### Status codes

| Code | Meaning                                        |
|------|------------------------------------------------|
| 202  | Every targeted channel accepted the message    |
| 207  | Some channels accepted it, some failed         |
| 502  | No channel accepted it                         |
| 422  | Validation failed (unknown channel/level, …)   |
| 405  | Wrong HTTP method (with an `Allow` header)     |

A failed channel reports why:

```json
{ "channel": "email", "delivered": false, "failure_reason": "Transport is down." }
```

## Design

```
LogController
     │  validates through StoreLogForm
     ▼
LogDispatcherInterface ──▶ LogChannelFactoryInterface ──▶ LogChannelInterface
   writes to the given        resolves and memoises          writes the message
   channels, collects         from the DI container          (email / file / db)
   the results
```

Three roles, three interfaces. A channel only knows how to write; it never
resolves other channels and holds no dispatch state.

**Failure boundary.** `LogDispatcher::writeTo()` resolves the channel *outside*
its try block on purpose:

- a channel missing from the configuration is a deployment bug and stays loud;
- a transport that breaks at runtime is an expected outcome and becomes a
  `LogDeliveryResult` with `delivered: false`.

One broken transport never stops the remaining channels.

**Configuration.** `config/loggers.php` holds the channel map, `config/container.php`
turns it into DI definitions. Both `config/web.php` and `config/test.php` include
the latter, so the tests exercise the same wiring the web app uses.

## Yii2-specific notes

- `LogLevel::toYiiLevel()` maps the enum onto Yii's integer level constants.
- `request.parsers` registers `yii\web\JsonParser`; Yii does not decode JSON
  request bodies without it.
- URL rules are not verb-scoped, so the controller's `VerbFilter` can answer a
  wrong method with 405 instead of a bare 404.
- `StoreLogForm` declares its input properties as `mixed`: a typed `?array`
  raises a `TypeError` on `{"channels": "file"}`, turning a 422 into a 500.
  The rules narrow the types, the accessors return them.

## Running it

```bash
composer install
php -S 0.0.0.0:8001 -t web web/index.php     # or: docker compose up  (:8000)
```

```bash
curl -X POST http://localhost:8001/api/logs \
  -H 'Content-Type: application/json' \
  -d '{"message":"Hello."}'
```

Channel output goes to `runtime/logs/app.log`. Ready-made requests live in
`http-requests/logs.http`.

## Checks

```bash
./vendor/bin/codecept run Unit,Functional   # 71 tests
./vendor/bin/phpcs                          # Yii2 standard
./vendor/bin/phpstan analyse                # level 5
```

The contact-page tests need the GD extension (Yii2's CAPTCHA); the bundled
`yiisoftware/yii2-php` image has it.
