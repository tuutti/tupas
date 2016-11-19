#TUPAS Authentication
[![Build Status](https://travis-ci.org/tuutti/tupas.svg?branch=8.x-1.x)](https://travis-ci.org/tuutti/tupas)

##Description

TUPAS Authentication module is a general tool for enabling strong session-based authentication in Drupal web applications.

TUPAS is an authentication service provided by the Federation of Finnish Financial Services (www.fkl.fi) and supported by most of the Finnish banks. Read more about TUPAS from http://www.finanssiala.fi/maksujenvalitys/dokumentit/Tupas-Service-Description-v24.pdf.

##Requirements

* Externalauth (https://www.drupal.org/project/externalauth) when using Tupas registration

##Usage

Configuration can be found from: Configuration > TUPAS authentication (/admin/config/people/tupas).

###Tupas session (submodule)

Tupas session submodule allows users to authenticate using tupas. Tupas session has no functionality besides the authentication process and is usually used in conjunction with an another module, such as Tupas registration.

####How to use Tupas session with your custom module

To check if user has an active tupas session:

```
<?php
$session_manager = \Drupal::service('tupas_session.session_manager');

if ($session_manager->getSession()) {
  // User is tupas authenticated.
}
?>
```

To check access on route, add:

```
  requirements:
    _require_tupas_session: 'TRUE'
```

to your `*.routing.yml` file.


###Tupas registration (submodule)

Tupas registration provides two different ways to register to a site, depending on `tupas_registration.disable_form` setting:

**1) Disable form setting is enabled**

Account will be created and logged in automatically without any user interaction.

**2) Disable form setting is disabled**

Users are allowed to register using a registration form.

####How to alter auto-generated username
Tupas attempts to use `B02K_CUSTNAME` return value (usually Firstname Lastname) as an username when available.

Username can be altered by responding to `Drupal\tupas_session\Event\SessionEvents::SESSION_ALTER` event and overriding the `B02K_CUSTNAME` in `Drupal\tupas_session\Event\SessionData::$data`.

####How to alter authname mapping (hashed SSN)

Hashed `B02K_CUSTID` is used to map the tupas sessios to the user. See `Drupal\tupas\Entity\TupasBank::hashResponseId()` for current implementation.

To create a custom implementation for authname mapping:

Create an event subscriber that responds to `Drupal\tupas_session\Event\SessionEvents::CUSTOMER_ID_ALTER` event and override `Drupa\tupas_session\Event\CustomerIdAlterEvent::$customerId` with your custom customer id.

All query parameters returned by the Bank can be found from `Drupal\tupas_session\Event\CustomerIdAlterEvent::$data['raw']`.

##Author

- Lauri Kolehmainen <http://drupal.org/user/436736>
- Juha Niemi <http://drupal.org/user/157732>
- Sampo Turve <http://drupal.org/user/669530>
- Lari Rauno <https://drupal.org/u/tuutti>

##Credits

- Exove Ltd (www.exove.com)
- Vesa Palmu / Moana (www.moana.fi)
- The Finnish Red Cross (www.punainenristi.fi)
- Mearra (www.mearra.com)
- KWD Digital (www.kwd.fi)
