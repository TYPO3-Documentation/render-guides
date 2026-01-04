..  include:: /Includes.rst.txt

======================
Confvals with subtrees
======================

Properties of CASE
==================

..  confval-menu::
    :name: typoscript-case-properties
    :caption: TypoScript Case Properties
    :display: table
    :type:

    ..  confval:: array of cObjects
        :name: case-array
        :type: cObject
        :searchFacet: TypoScript

        Array of cObjects. Use this to define cObjects for the different
        values of `cobj-case-key`. If `cobj-case-key` has a certain value,
        the according cObject will be rendered. The cObjects can have any name, but not
        the names of the other properties of the cObject CASE.

    ..  confval:: cache
        :name: case-cache
        :type: cache
        :searchFacet: TypoScript

        See  for details.

    ..  confval:: default
        :name: case-default
        :type: cObject
        :searchFacet: TypoScript

        Use this to define the rendering for *those* values of cobj-case-key that
        do *not* match any of the values of the cobj-case-array-of-cObjects. If no
        default cObject is defined, an empty string will be returned for
        the default case.

    ..  confval:: if
        :name: case-if
        :type: ->if
        :searchFacet: TypoScript

        If if returns false, nothing is returned.


Properties of COA
=================


..  confval-menu::
    :display: table
    :type:

    ..  confval:: 1,2,3,4...
        :name: coa-array
        :type: cObject
        :searchFacet: TCA

        Numbered properties to define the different cObjects, which should be
        rendered.

    ..  confval:: cache
        :name: coa-cache
        :type: cache
        :searchFacet: TCA

        See cache function description for details.

    ..  confval:: if
        :name: coa-if
        :type: ->if <if>
        :searchFacet: TCA

        If `if` returns false, the COA is **not** rendered.

Long default values
===================

..  confval-menu::
    :name: typoscript
    :display: table
    :type:
    :default: max=20
    :test:

    ..  confval:: pages
        :name: typoscript-pages
        :type: string
        :default: `{$styles.content.loginform.pid}`
        :test: `1`

        Define the User Storage Page with the Website User Records, using a
        comma separated list or single value

    ..  confval:: redirectPageLoginError
        :name: typoscript-redirectPageLoginError
        :type: integer
        :default: `{$styles.content.loginform.redirectPageLoginError}`

        Page id to redirect to after Login Error

    ..  confval:: dateFormat
        :name: typoscript-dateFormat
        :type: date-conf
        :default: Y-m-d H:i

    ..  confval:: email
        :name: typoscript-email

        ..  confval:: email.templateRootPaths
            :name: typoscript-email.templateRootPaths
            :type: array
            :default: `{$styles.content.loginform.email.templateRootPaths}`

            Path to template directory used for emails

    ..  confval:: exposeNonexistentUserInForgotPasswordDialog
        :name: typoscript-exposeNonexistentUserInForgotPasswordDialog
        :type: bool
        :default: {$styles.content.loginform.exposeNonexistentUserInForgotPasswordDialog}

        If set and the user account cannot be found in the forgot password
        dialogue, an error message will be shown that the account could not be
        found.

    ..  confval:: title
        :type: string (language reference)
        :name: widget-tag-title
        :required:
        :Example: `LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title`

        Defines the title of the widget. Language references are resolved.

..  confval-menu::
    :display: table
    :name: site-setting-definition
    :type:
    :required:

    ..  confval:: categories
        :type: array
        :name: site-settings-definition-categories

        ..  confval:: label
            :type: string
            :name: site-settings-definition-categories-label

        ..  confval:: parent
            :type: :confval:`site-settings-definition-categories` key
            :name: site-settings-definition-categories-parent

    ..  confval:: settings
        :type: array
        :name: site-settings-definition-settings

        ..  confval:: label
            :type: string
            :name: site-settings-definition-settings-label

        ..  confval:: description
            :type: string
            :name: site-settings-definition-settings-description

        ..  confval:: category
            :type: :confval:`site-settings-definition-categories` key
            :name: site-settings-definition-settings-category

        ..  confval:: type
            :type: definition type
            :name: site-settings-definition-settings-type
            :required:

        ..  confval:: default
            :type: mixed
            :name: site-settings-definition-settings-default
            :required:

            The default value must have the same type like defined in
            site-settings-definition-settings-type.

        ..  confval:: readonly
            :type: bool
            :name: site-settings-definition-settings-readonly

            If a site setting is marked as readonly, it can be overridden only
            by editing  the :file:`config/sites/my-site/settings.yaml` directly,
            but not from within the editor.

