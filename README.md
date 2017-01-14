# bot-meetup
Cliente Telegram para a plataforma do meetup.com

Versão de testes, requer melhorias para poder ser utilizado. Username: http://t.me/SocialMeetupBot

## Instalação
Crie um arquivo .env na raiz do projeto com o seguinte conteúdo:
```
TELEGRAM_BOT_TOKEN=<TOKEN>
TELEGRAM_USERNAME=<BotUsername>
CLIENT_ID=<MeetupClientId>
CLIENT_SECRET=<MeetupClientSecret>
REDIRECT_URI=<MeetupOauth2CallbackUrl>
PHINX_ENVIRONMENT=<production|development>
DATABASE_URL=<DSNPrefix>://<DB_USERNAME>:<DB_PASSWD>@<DB_HOST>:<DB_PORT>/<DB_NAME>
MOCK_JSON=''
```

Substitua os valores das tags pelos dados de seu ambiente.

A environment MOCK_JSON serve para colocar um json das requisições do telegram e fazer mock delas.

Após criado o arquivo .env, execute o composer:
```
composer install
```

Para testar a aplicação, coloque algum json de update do telegram na environment MOCK_JSON e pode executar o arquivo index.php no próprio cli `php index.php`

Para fazer deploy para produção, faça um pull request para este repositório. Assim que seue pull request for aprovadom automaticamente o bot @SocialMeetupBot será atualizado com seu código.
