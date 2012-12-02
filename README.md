PrestaShop-Opauth
=================
Opauth plugin for PrestaShop v1.4.x, allowing simple plug-n-play 3rd-party authentication with PrestaShop 1.4.x Version
- Project Managers
  [Amaury PLANÇON][1]
	
- Developers/Programmers
  [Open Presta][2]
  [Amaury PLANÇON][1]

Implemented based on
- [Opauth][3] - Multi-provider authentication framework for PHP

- [EmailVerify][4] - PrestaShop module by Mellow

Changelog
---------

####v1.1.0 _(01 December 2012)_
- LinkedIn
- PayPal

####v1.0.1 _(01 December 2012)_
- Quick Fix

####v1.0.0 _(01 December 2012)_
- Initial release allowing simple plug-n-play 3rd-party authentication for Facebook, Twitter & Google

Available strategies
--------------------
A strategy is a set of instructions that interfaces with respective authentication providers and relays it back to Opauth.

Provider-specific:

- Facebook
  @author	uzyn
  @link		https://github.com/uzyn/opauth-facebook

- Twitter
  @author	uzyn
  @link		https://github.com/uzyn/opauth-twitter

- Google
  @author	uzyn
  @link		https://github.com/uzyn/opauth-google

- LinkedIn
  @author	uzyn
  @link		https://github.com/uzyn/opauth-linkedin

- PayPal
  @author	24hours
  @link		https://github.com/24hours/opauth-paypal

Requirements
-------------
PHP 5 (>= 5.2)

Contribute
----------
PrestaShop-Opauth needs your contributions, especially the following:

- Issues 
  Refer to [issues](https://github.com/aPlancon69/PrestaShop-Opauth/issues) to see open issues.

How to Contribute
------------------

1. Fork it.
2. Create a branch (`git checkout -b my_markup`)
3. Commit your changes (`git commit -am "Added Snarkdown"`)
4. Push to the branch (`git push origin my_markup`)
5. Open a [Pull Request][5]

License
---------
PrestaShop-Opauth is GNU General Public License (GPL)
Copyright © 2012 Amaury PLANÇON (http://www.amaury-plancon.com/)

[1]: http://www.amaury-plancon.com/
[2]: http://www.openpresta.com/
[3]: https://github.com/uzyn/opauth
[4]: http://www.prestashop.com/forums/topic/168254-module-controleur-envoi-dun-mail-unique-de-bienvenue-et-de-validation-de-compte/
[5]: https://github.com/aPlancon69/PrestaShop-Opauth/pulls