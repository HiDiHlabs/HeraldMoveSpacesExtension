# Phabricator Herald Spaces extension

This Phabricator extension integrates the spaces extension with Herald's automatic rules.
As the name implies, it allows Herald to move items to another space.

We wrote this because we operate on (highly sensitive) human genome data, and not everyone in
our department is allowed to see all data associated with all participants, in accordance with the
principle of "Datensparsamkeit" ("Data economy"/need-to-know principle) anchored in
German privacy law.
By automatically assigning the spaces based on tags, the potential for human error is greatly
reduced, and user-experience is improved because they have only one action to execute:
"add the tag"", instead of the error-prone "add the tag AND don't forget to set the space"

## Installation:

* Put `ManiphestTaskSpacesHeraldAction.php` in `./src/extensions` of the Phabricator installation directory. 

* If you want to use the "Is Exactly" Herald condition, it is best to apply this patch on a separate branch, to make the future updates of Phabricator less painful. 

```sh
cd /path/to/phabricator
git checkout -b SpacesExtensionIsExactly
patch -p1 < HeraldConditionIsExactly.patch

# each time you update to a new version of Phabricator
# (each time you update the Phabricator repository)

git checkout master
git pull
git checkout SpacesExtensionIsExactly
git merge master
```

## Features

* adds a Herald action "Move to space"
Does exactly what it says on the tin, allows Herald rules to move an object into the specified
space.
* _optional_: adds a Herald condition "is exactly" (see `HeraldConditionIsExactly.patch`)
"Is exactly" _can_ match multiple tags simultaneously, but is very litteral: the tags on the
item must be _exactly_ the list specified in the Herald rule, no more, no less 
(multiple items possible).
Any excess tags (also those that don't have spaces associated with them) 'break' the match.
The alternative is to have each space-moving rule say "if tags includes tagX AND tags don't
include tagY,TagZ,TagA...", which quicly becomes unmanageably quadratic.


## Known Shortcomings (CANT_FIX)

1. Herald doesn't understand that there can only ever be one space.
if multiple Herald-rules apply to the object, and they all try to set the space, the item
will end up being in the space of whichever rule applied last.
(This is usually the rule that was created the most recently, as Herald-rules seem to be evaluated
in database-order)
Fortunately, the item's history will show that Herald moved the item multiple times, so it
shouldn't be to hard to diagnose if/when this happens.
