#!/bin/bash
#set -x 

delPath()
{
	if [ -e ${1} ]; then
		echo -n "${1} exists, delete? [Y/n] "
		read answer

		case ${answer} in
			[Yy] | '')
				rm -rf ${1}
				;;
			*)
				echo "${1} exists, cannot continue."
				exit 1
				;;
		esac
	fi
}
		

SOURCE="/var/www/phptelemeter/trunk"
TARGET="/tmp"

FTPDIR="software/phptelemeter"
FTPFILE="~/.ncftp/ftpaccess"

# SF info
SFFTPHOST="upload.sf.net"
SFFTPDIR="incoming"

if [ ! -e ${SOURCE}/phptelemeter.inc.php ]; then
	echo "error: could not find phptelemeter in ${SOURCE}."
	echo "Are you sure this path is correct?"
	exit 1;
fi

REL=$(cat phptelemeter.inc.php | grep define\(\"_version\" | cut -d \" -f 4)

RELNAME="phptelemeter-${REL}"
RELPATH="${TARGET}/${RELNAME}"
RELTAR="${RELPATH}.tar.gz"

VERFILE="${TARGET}/VERSION"

delPath ${RELPATH}
delPath ${RELTAR}
delPath ${VERFILE}

mkdir -p ${RELPATH}

cp -r ${SOURCE}/* ${RELPATH}

mv ${RELPATH}/docs/gpl.txt ${RELPATH}

# clean out
CLEANFILES="*.session *.webprj docs mkrelease.sh patches CVS .svn *~ *.tmproj"

for cleanitem in ${CLEANFILES}; do
	find ${RELPATH} -name "${cleanitem}" -exec rm -fr {} \;
done

echo ${REL} > ${VERFILE}

# tar it up
pushd /tmp 2>&1 >/dev/null
tar cfz ${RELTAR} ${RELNAME}
popd 2>&1 >/dev/null
echo "Release is available in ${RELPATH}.tar.gz"

echo -n "Do you want to upload the files? [Y/n] "
read answer

case ${answer} in
	[Yy] | '')
		ncftpput ${SFFTPHOST} ${SFFTPDIR} ${RELTAR}
		ncftpput -f ${FTPFILE} ${FTPDIR} ${VERFILE}
		
		;;
esac

