# Auth Server Connect
Biblioteca de integração com o Auth Server para geração de tokens de acesso ou validação de tokens de acesso em rotas autenticadas.

## Tópicos
- [Instalação](#instalação)
- [Geração de tokens](#geração-de-tokens)
    - [Configuração básica](#configuração-básica)
    - [Provedores](#provedores)
- [Validação de tokens](#validação-de-tokens)
- [Propriedades de configuração](#propriedades-de-configuração)

## Instalação
Execute o comando abaixo para instalar a biblioteca:
```
composer require felipemenezesdm/auth-server-connect-laravel:^1.0
```

No arquivo **app > app > Http > Kernel.php**, informe o seguinte valor `\App\Http\Middleware\AuthServerConnect::class`:
```php
protected $middlewareGroups = [
  'web' => [
      // [...]
      \App\Http\Middleware\AuthServerConnect::class,
  ],
  
  'api' => [
      // [...]
  ],
];
```

## Geração de tokens
### Configuração básica
Após a instalação da lib, é possível utilizar o atributo #[AuthServerConnection] nos controladores da aplicação: 
```php
#[AuthServerConnection]
class MyController extends Controller
{
    public static ?AuthServerToken $authServerToken;
}
```
```dotenv
AUTH_SERVER_PROVIDER=environment
AUTH_SERVER_URI=http://localhost:1080/api/v1
client_id=9835e498-eb18-4d7a-8e8e-230b2190df1f
client_secret=51469bf9-081f-4023-a391-b94877bb5b1b
```

Vale destacar que uma propriedade estática do tipo AuthServerToken deve ser criada no controlador, conforme o exemplo acima. Ela será o objeto de manipulação do token. 

### Provedores
Atualmente, a biblioteca disponibiliza três formas para obter as credenciais para geração de tokens no Auth Server: **environment**, **aws** e **gcp**.

#### Environment
As credenciais podem ser definidas por meio de variáveis de ambiente no sistema.
```dotenv
AUTH_SERVER_PROVIDER=environment
AUTH_SERVER_URI=http://localhost:1080/api/v1
```
```shell
export client_id=9835e498-eb18-4d7a-8e8e-230b2190df1f
export client_secret=51469bf9-081f-4023-a391-b94877bb5b1b
```

#### AWS
As credenciais podem ser obtidas a partir das Secrets da Amazon Web Services.
```dotenv
AUTH_SERVER_PROVIDER=aws
AUTH_SERVER_URI=http://localhost:1080/api/v1
AUTH_SERVER_SECRET_NAME=my-secret-name
AUTH_SERVER_REGION=us-east-1
```

#### GCP
As credenciais podem ser obtidas a partir das Secrets do Google Cloud Platform.
```dotenv
AUTH_SERVER_PROVIDER=gcp
AUTH_SERVER_URI=http://localhost:1080/api/v1
AUTH_SERVER_SECRET_NAME=my-secret-name
AUTH_SERVER_PROJECT_ID=gcp-project
```

## Validação de tokens
Para validar um token, você pode configrar o atributo #[AuthServerValidation] no método da rota e/ou no próprio controlador.

Usando a construção abaixo, a autenticação será implementada para todas as rotas do controlador:
```php
#[AuthServerValidation(scopes: ['my-scope'])]
class MyController extends Controller
{
    #[Route(name: 'my-route', path: '/api/v1')]
    public function myRoute() : void
    {
    }
}
```

Usano a construção abaixo, a autenticação será implementada apenas para as rotas individualmente definidas:
```php
class MyController extends Controller
{
    #[AuthServerValidation(scopes: ['my-scope'])]
    #[Route(name: 'my-route', path: '/api/v1')]
    public function myRoute() : void
    {
    }
}
```

Usando a construção abaixo, a autenticação será implementada para todos as rotas do controlador, porém os escopos de acesso serão mesclados. Neste exemplo, todos as rotas terão o escopo básico "my-scope", porém a rota "/api/v1/{id}" terá o escopo "my-scope-2" como adicional:
```php
#[AuthServerValidation(scopes: ['my-scope'])]
class MyController extends Controller
{
    #[AuthServerValidation(scopes: ['my-scope-2'])]
    #[Route(name: 'my-route', path: '/api/v1/{id}')]
    public function myRoute() : void
    {
    }
    
    #[Route(name: 'my-route2', path: '/api/v1')]
    public function myRoute2() : void
    {
    }
}
```

Lembrando que o parameter _scopes_ será utilizado para identificar se o token passado para a requisição possui o (s) escopo (s) necessário (s) para acesso da rota.

## Propriedades de configuração
Descrição de todas as propriedades de configuração disponíveis para a biblioteca.

- **AUTH_SERVER_ENABLED:**
    - **tipo:** _boolean_
    - **descrição:** definir se as validações estão habilitadas ou desabilitadas.
    - **obrigatório:** sim
    - **padrão:** true
- **AUTH_SERVER_NAME:**
    - **tipo:** _string_
    - **descrição:** nome de identificação do conector
    - **obrigatório:** não
    - **padrão:** _null_
- **AUTH_SERVER_URI:**
    - **tipo:** _string_
    - **descrição:** URL padrão para o servidor do Auth Server
    - **obrigatório:** sim
- **AUTH_SERVER_REDIRECT_URI:**
    - **tipo:** _string_
    - **descrição:** URL de redirecionamento do cliente
    - **obrigatório:** não
    - **padrão:** _null_
- **AUTH_SERVER_GRANT_TYPE:**
    - **tipo:** _string_
    - **descrição:** tipo de concessão do token
    - **obrigatório:** sim
    - **padrão:** client_credentials
- **AUTH_SERVER_SCOPES:**
    - **tipo:** _string_
    - **descrição:** escopos para geração do token, seperados por vírgula. Ex: scope1,scope2,scope2
    - **obrigatório:** não
    - **padrão:** null
- **AUTH_SERVER_PROVIDER:**
    - **tipo:** _string_
    - **descrição:** tipo de provedor de credencials. Atualmente disponíveis: **environment**, **aws** e **gcp**
    - **obrigatório:** sim
    - **padrão:** environment
- **AUTH_SERVER_PROJECT_ID**
    - **tipo:** _string_
    - **descrição:** exclusivo para o provedor **gcp**, para identificar o projeto do qual as credencials serão obtidas.
    - **obrigatório:** sim, quando o provedor for **gcp**
- **AUTH_SERVER_REGION:**
    - **tipo:** _string_
    - **descrição:** exclusivo para o provedor **aws**, para identificar a região padrão do cliente.
    - **obrigatório:** sim, quando o provedor for **aws**
- **AUTH_SERVER_CLIENT_ID_KEY:**
    - **tipo:** _string_
    - **descrição:** definir a chave para o ID do cliente no payload de secrets ou nas variáveis de ambiente. Neste caso, esta configuração é válida para os provedores **aws**, **gcp** e **environment**. Por exemplo, se o client-id-key for definido como "my-client-id" e o provedor for "environment", será necessário criar uma variável de ambiente chamada "my-client-id" para armazenar o client-id que será usado para geração de tokens.
    - **obrigatório:** sim
    - **padrão:** client_id
- **AUTH_SERVER_CLIENT_SECRET_KEY:**
    - **tipo:** _string_
    - **descrição:** definir a chave para a secret do cliente no payload de secrets ou nas variáveis de ambiente. Neste caso, esta configuração é válida para os provedores **aws**, **gcp** e **environment**. Por exemplo, se o client-secret-key for definido como "my-client-secret" e o provedor for "environment", será necessário criar uma variável de ambiente chamada "my-client-secret" para armazenar o client-secret que será usado para geração de tokens.
    - **obrigatório:** sim
    - **padrão:** client_secret
- **AUTH_SERVER_END_POINT:**
    - **tipo:** _string_
    - **descrição:** quando o provedor for igual a **aws**, este parâmetro pode ser configurado para definir o endpoint de onde serão obtidas as credenciais. É bastante útil para quando se está utilizando o LocalStack, por exemplo.
    - **obrigatório:** não
- **AUTH_SERVER_SECRET_NAME:**
    - **tipo:** _string_
    - **descrição:** definir o nome da secret onde serão obtidas as credenticiais. Válido para os provedores **aws** e **gcp**.
    - **obrigatório:** sim, quando o provedor for **aws** ou **gcp**
