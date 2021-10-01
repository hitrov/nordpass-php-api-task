# Secure Information Storage REST API

<p align="center">
  <a href="https://github.com/hitrov/nordpass-php-api-task/actions"><img src="https://github.com/hitrov/nordpass-php-api-task/workflows/Tests/badge.svg" alt="Test"></a>
</p>

## Project setup

* Add `secure-storage.localhost` to your `/etc/hosts`: `127.0.0.1 secure-storage.localhost`

* Run `make init` to initialize project 

If you got such error on that step:
```
  An exception occurred in driver: SQLSTATE[HY000] [2002] Connection refused  
```
just run `make init` again.

* Open in browser: http://secure-storage.localhost:8000/item Should get `Full authentication is required to access this resource.` error, because first you need to make `login` call (see `postman_collection.json` or `SecurityController` for more info).

## Run tests

make tests

## API credentials

* User: john
* Password: maxsecure

## Postman requests collection

You can import all available API calls to Postman using `postman_collection.json` file

## Encryption
Migration `Version20210930163131` will encrypt all user items data. 
Unlock key is random, password-protected, and unique for each user. 
Password for unlock is being generated this way:
```php
hash('sha256', sprintf("%s.%s", $user->getPassword(), $this->params->get('encryption_secret')))
```
where `$user->getPassword()` is hashed/salted password from the DB 

and `$this->params->get('encryption_secret')` is env var `ENCRYPTION_SECRET` value. 

For even better security [Secrets](https://symfony.com/doc/current/configuration/secrets.html) can be used, no code changes will be needed. 

### NB!
You MUST re-encrypt user items data when user changes his password.

### Important Information
**In case of changing the value of `ENCRYPTION_SECRET` *ALL* user data must be re-encrypted.**

### Clear plain text items
Please review the branch `drop-plain-data-column` - it contains needed migration `Version20210930214756`. 

## Endpoints
### Authorization
#### Login
`POST /login`

Example request (JSON):
```json
{
    "username": "john",
    "password": "maxsecure"
}
```
Session will be created on server side. Requests to the rest of endpoints must contain proper Cookie (PHPSESSID) from the response headers.

Example success response (JSON):
```json
{
    "username": "john",
    "roles": [
        "ROLE_USER"
    ]
}
```

#### Logout
`POST /logout`

Body is not required. Session will be destroyed on the server side.

### Items
#### List
`GET /item`

Example success response (JSON):
```json
[
    {
        "id": 4,
        "data": "item secret",
        "created_at": {
            "date": "2021-09-30 21:09:32.000000",
            "timezone_type": 3,
            "timezone": "UTC"
        },
        "updated_at": {
            "date": "2021-09-30 21:22:10.000000",
            "timezone_type": 3,
            "timezone": "UTC"
        }
    },
    ...
]
```

#### Create Item
`POST /item`

Example request (`Content-Type: multipart/form-data`):
```shell
curl --location --request POST 'http://secure-storage.localhost:8000/item' \
--header 'Cookie: PHPSESSID=***' \
--form 'data=item secret'
```
or `Content-Type: application/x-www-form-urlencoded`
```shell
curl --location --request POST 'http://secure-storage.localhost:8000/item' \
--header 'Cookie: PHPSESSID=***' \
--data "data=newest item secret"
```

Example success response (JSON):
```json
[]
```
Example error response (JSON) - status code 400 Bad Request:
```json
{
    "error": "No data parameter"
}
```

#### Update Item
`PUT /item`

Example request (`Content-Type: application/x-www-form-urlencoded`)
```shell
curl --location --request PUT 'http://secure-storage.localhost:8000/item' \
--header 'Cookie: PHPSESSID=***' \
--data "id=4&data=newest item secret"
```
Example success response (JSON):
```json
{
    "id": 4,
    "data": "newest item secret",
    "created_at": {
        "date": "2021-09-30 21:09:32.000000",
        "timezone_type": 3,
        "timezone": "UTC"
    },
    "updated_at": {
        "date": "2021-09-30 21:26:52.000000",
        "timezone_type": 3,
        "timezone": "UTC"
    }
}
```
Example error responses (JSON) - status code 400 Bad Request:
```json
{
    "error": "No data parameter"
}
```
```json
{
    "error": "No id parameter"
}
```
```json
{
    "error": "No item"
}
```

#### Delete Item
`DELETE /item/{id}`

No body is required.

Example success response (JSON):
```json
[]
```
Example error responses (JSON) - status code 400 Bad Request:
```json
{
    "error": "No id parameter"
}
```
```json
{
    "error": "No item"
}
```