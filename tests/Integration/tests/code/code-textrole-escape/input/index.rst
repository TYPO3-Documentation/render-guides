==========================
Code text role escape tests
==========================

Double backslash resolves to single backslash:

:rst:`a\\b`

:shell:`path\\to\\file`

Plain content without escapes still works:

:rst:`rest code`

Multiple roles with double backslash:

:typoscript:`config.no\\cache`

:yaml:`key\\value`
