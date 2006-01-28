#!/bin/bash
#set -x 

SOURCE="/var/www/phptelemeter"
TARGET="/tmp"
FTPHOST="upload.sf.net"
FTPDIR="incoming"

REL=$(cat phptelemeter.inc.php | grep define\(\"_version | cut -d \" -f 4)

RELNAME="phptelemeter-${REL}"

RELPATH="${TARGET}/${RELNAME}"
RELTAR="${RELPATH}.tar.gz"

if [ -e ${RELPATH} ]; then
	echo -n "${RELPATH} exists, delete? [Y/n] "
	read answer

	case ${answer} in
		[Yy] | '')
			rm -rf ${RELPATH}
			;;
		*)
			echo "${RELPATH} exists, cannot continue."
			exit 1
			;;
	esac
fi

if [ -e ${RELTAR} ]; then
        echo -n "${RELTAR} exists, delete? [Y/n] "
        read answer

        case ${answer} in
                [Yy] | '')
                        rm -f ${RELTAR}
                        ;;
                *)
                        echo "${RELTAR} exists, cannot continue."
                        exit 1
                        ;;
        esac
fi


mkdir -p ${RELPATH}

cp -r ${SOURCE}/* ${RELPATH}

mv ${RELPATH}/docs/gpl.txt ${RELPATH}
# clean out
rm -fr ${RELPATH}/*.session ${RELPATH}/*.webprj ${RELPATH}/CVS ${RELPATH}/modules/CVS ${RELPATH}/modules/libs/CVS ${RELPATH}/docs ${RELPATH}/mkrelease.sh

#tar it up
pushd /tmp 2>&1 >/dev/null
tar cfz ${RELTAR} ${RELNAME}
popd 2>&1 >/dev/null
echo "Release is available in ${RELPATH}.tar.gz"

echo -n "Do you want to upload it to ${FTP}? [Y/n] "
read answer

case ${answer} in
	[Yy] | '')
		ncftpput ${FTPHOST} ${FTPDIR} ${RELTAR}
		
		;;
esac

