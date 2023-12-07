#!/usr/bin/env sh

# TODO: This detection seems to fail on macOS. If no "--user" argument is specified to make Docker run
#       as that user, it cannot deduce ownership properly. Probably the Dockerfile needs to be adapted
#       so that a user-switch to the "typo3" user is done? This can have side-effects though.
MY_UID="$(id -u)"
echo "UID: $MY_UID"

if [ "$MY_UID" -eq "0" ]; then

  UID=$(stat -c "%u" $(pwd))
  GID=$(stat -c "%g" $(pwd))

  if [ "$UID" -eq "0" ]; then
      echo "Run-as: root"
  else
      echo "Run-as: $UID (custom invocation)"
      addgroup typo3 --gid=$GID;
      adduser -h $(pwd) -D -G typo3 --uid=$UID typo3;

      # su behaves inconsistently with -c followed by flags
      # Workaround: run the entrypoint and commands as a standalone script
      echo "#!/usr/bin/env sh" > /usr/local/bin/invocation.sh
      echo >> /usr/local/bin/invocation.sh
      echo "export SHELL_VERBOSITY=$SHELL_VERBOSITY" >> /usr/local/bin/invocation.sh
      for ARG in "$@"; do
          printf "${ARG} " >> /usr/local/bin/invocation.sh
      done
      chmod a+x /usr/local/bin/invocation.sh

      echo "Invocation:"
      cat /usr/local/bin/invocation.sh
      echo ""
      su - typo3 -c "/usr/local/bin/invocation.sh"
  fi
else
  echo "Run-as: Owner $MY_UID"
  echo "Invocation: php $@"
  php "$@"
fi
