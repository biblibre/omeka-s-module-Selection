Selection (module for Omeka S)
==============================

This is the Common-independant version of Selection. To see the original version,
please see [Selection].

Selection is a module for [Omeka S] that allows any users to store one or
multiple selections and the same for any visitor via local session. Each
selections can be saved with a label to simplify management and it is possible
to create a directory-like hierarchy to arrange resources. The selection can be
dynamic too when a search query is used.

Furthermore, when the module [Bulk Export] is installed, it is possible to
export them instantly to common formats, included common spreadsheet formats.

The selection is saved in a cookie for anonymous visitor, so anybody can create
a selection. When the user is authenticated, in particular as a [Guest], the
selections is saved in the database and available permanently.

Comparison with the original [Selection] module
---------

This module was created to make the original aforementioned Selection module
independant to the Common module.
Unlike [Selection], this mode:
- does not need any external module.
- has more straightforward code.
- has less configuration options. (TODO)

Installation
------------

See general end user documentation for [installing a module].

Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitLab.


License
-------

This module is published under the [CeCILL v2.1] license, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

This software is governed by the CeCILL license under French law and abiding by
the rules of distribution of free software. You can use, modify and/ or
redistribute the software under the terms of the CeCILL license as circulated by
CEA, CNRS and INRIA at the following URL "http://www.cecill.info".

As a counterpart to the access to the source code and rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors have only limited liability.

In this respect, the user’s attention is drawn to the risks associated with
loading, using, modifying and/or developing or reproducing the software by the
user in light of its specific status of free software, that may mean that it is
complicated to manipulate, and that also therefore means that it is reserved for
developers and experienced professionals having in-depth computer knowledge.
Users are therefore encouraged to load and test the software’s suitability as
regards their requirements in conditions enabling the security of their systems
and/or data to be ensured and, more generally, to use and operate it in the same
conditions as regards security.

The fact that you are presently reading this means that you have had knowledge
of the CeCILL license and that you accept its terms.


Copyright
---------

* Copyright Biblibre, 2016-2025 (see [Biblibre])
* Copyright Daniel Berthereau, 2017-2025 (see [Daniel-KM] on GitLab)

This module was initially based on the fork of the module [Basket] from BibLibre
and restructured and improved for various projects, like [Collections de la Maison de Salins],
and [Bibliothèque numérique] of Université Paris Sciences et Lettres ([PSL]).


[Selection]: https://gitlab.com/Daniel-KM/Omeka-S-module-Selection
[Omeka S]: https://omeka.org/s
[Common]: https://gitlab.com/Daniel-KM/Omeka-S-module-Common
[Guest]: https://gitlab.com/Daniel-KM/Omeka-S-module-Guest
[Bulk Export]: https://gitlab.com/Daniel-KM/Omeka-S-module-BulkExport
[installing a module]: https://omeka.org/s/docs/user-manual/modules/#installing-modules
[module issues]: https://github.com/biblibre/omeka-s-module-Selection/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[Basket]: https://github.com/BibLibre/Omeka-S-module-Basket
[Biblibre]: https://github.com/biblibre
[GitLab]: https://gitlab.com/Daniel-KM
[Collections de la Maison de Salins]: https://collections.maison-salins.fr
[Bibliothèque numérique]: https://bibnum.explore.psl.eu
[PSL]: https://psl.eu
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"
