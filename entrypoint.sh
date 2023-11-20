#!/usr/bin/env sh

# TODO: This detection seems to fail on macOS. If no "--user" argument is specified to make Docker run
#       as that user, it cannot deduce ownership properly. Probably the Dockerfile needs to be adapted
#       so that a user-switch to the "typo3" user is done? This can have side-effects though.
if [ "$(id -u)" -eq "0" ]; then

  UID=$(stat -c "%u" $(pwd))
  GID=$(stat -c "%g" $(pwd))

  if [ "$UID" -eq "0" ]; then
        echo "ERROR: Could not detect the owner of $(pwd) - did you mount your project?"
        echo ""
        echo "Run this container with:"
        echo "\"docker run --rm --volume \${PWD}:/project ghcr.io/typo3-documentation/render-guides:main\""
        echo ""
        echo "On macOS you might need to specify the user:"
        echo "\"docker run --rm --volume --user=\$(id -u):\$(id -g) \${PWD}:/project ghcr.io/typo3-documentation/render-guides:main\""
        echo ""
        exit 1
  else
      addgroup typo3 --gid=$GID;
      adduser -h $(pwd) -D -G typo3 --uid=$UID typo3;

      # su behaves inconsistently with -c followed by flags
      # Workaround: run the entrypoint and commands as a standalone script
      echo "#!/usr/bin/env sh" > /usr/local/bin/invocation.sh
      echo >> /usr/local/bin/invocation.sh
      for ARG in "$@"; do
          printf "${ARG} " >> /usr/local/bin/invocation.sh
      done
      chmod a+x /usr/local/bin/invocation.sh

      su - typo3 -c "/usr/local/bin/invocation.sh"
  fi
else
  php "$@"
fi
