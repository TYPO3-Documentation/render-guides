..  include:: /Includes.rst.txt

..  _HotReload:

==================================
HotReloading changes in ReST files
==================================

..  note::

    This feature is work in progress, and not yet as straight-ahead as it could be in the future!

If you write larger ReST documentation files, you usually repeat the process of saving,
rendering, reloading the browser many times.

To make this easier, when you use this project with DDEV you can run a NodeJS+Gulp+Browsersync
stack. Whenever a :file:`.rst` file in :file:`Documentation/` is updated, the rendering is
triggered, and your browser can be reloaded automatically once the corresponding :file:`.html`
file was created.

..  note::

    As a prerequisite, you need to have the DDEV instance up and running properly already, so you
    must have run `ddev composer install` in the main project beforehand.


You can achieve hot reloading via these shell commands (within this project directory):

..  code-block:: shell

    # Start DDEV, this is the prerequisite. It will forward a default proxy port "3000",
    # so that you can view the hot-reloading contents.
    ddev start

    # When you do this for the first time, you need to install the NodeJS project.
    # (Sadly, you may have to ignore the security advisories - the packages are only
    # devDependencies, so the vulnerabilities should not be relevant for local usage)
    ddev exec 'cd tools/rendersync && npm install'

    # After that you can always launch the browsersync proxy:
    ddev exec 'cd tools/rendersync && gulp serve'

Now open your browser with this URL: `https://render-guides.ddev.site:3000/ <https://render-guides.ddev.site:3000/>`__

Despite the console information it is vital that you use :file:`https://` and NOT :file:`http://`!

Now you can edit any :file:`Documentation/*.rst` file in your editor, move the browser
window next to it, and see it performing the automatic reload.

The delay of seeing the results after a change should be in the range of 3-4 seconds (due to full
rendering). The scroll position of your document will be retained.

..  note::

    Note that some IDEs like PhpStorm by default only save a file when moving the focus outside of
    the editing window.


You can quit the process by closing your terminal window or hitting `Ctrl-C`.

Details about the implementation
--------------------------------

The file `tools/rendersync/gulpfile.js` contains very simple logic to setup a GULP watch task
on both the :file:`.rst` and :file:`.html` files. The rest is all done via browserSync.

The proxy server takes any input URI, calls the actual URI in the context of the main DDEV
instance (with it's DocumentRoot set to :file:`Documentation-GENERATED-temp`) and then modifies
the output to inject the BrowserSync javascript on the fly. The GULP watch task then creates
a inotify job on the watched files, and on each change of a file makes the javascript watchers
running inside the browser (with the proxy-contents) trigger a browser reload.

The actual rendering on changes of the :file:`.rst` files is also triggered within a GULP watch
task, and currently re-renders the whole :file:`Documentation` folder again. Since this is
running within DDEV, we can use the local rendering instead of needing to boot up a docker instance.
This is achieved by running :shell:`make docs ENV=local` from the gruntfile.

The gruntfile currently uses extra :javascript:`console.log` calls to show which file is getting
watched, reloaded and requested.

=====
To-Do
=====

*   Create a composer package for this, so that it can be required for the documentation
    of extensions easily.

*   Check out how only the changed file can be re-rendered (this would then not properly
    reference TOC and Menu link changes).

*   Test/Make it work without DDEV.

