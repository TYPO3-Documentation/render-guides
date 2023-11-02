#!/usr/bin/env sh


if [ "$(id -u)" -eq "0" ]; then

  UID=$(stat -c "%u" $(pwd))
  GID=$(stat -c "%g" $(pwd))

  addgroup typo3 --gid=$GID;
  adduser -h $(pwd) -D -G typo3 --uid=$UID typo3;

  # su behaves inconsistently with -c followed by flags
  # Workaround: run the entrypoint and commands as a standalone script
  echo "#!/usr/bin/env sh" >> /usr/local/bin/invocation.sh
  echo >> /usr/local/bin/invocation.sh
  for ARG in "$@"; do
      printf "\"${ARG}\" " >> /usr/local/bin/invocation.sh
  done
  chmod a+x /usr/local/bin/invocation.sh

  su - typo3 -c "/usr/local/bin/invocation.sh"
fi
