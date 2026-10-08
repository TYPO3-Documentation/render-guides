..  _start:

=============================
Link text in a command's help
=============================

A command's help comes from the JSON the command writes, not from the manual,
so a link there without a text of its own is not reported:

..  console:command:: cache:warmup
    :json: command.json
