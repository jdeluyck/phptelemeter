#!/bin/bash
#set -x 

SOURCE="/var/www/phptelemeter/trunk"
TARGET="/tmp"

FTPDIR="software/phptelemeter"
FTPFILE="~/.ncftp/ftpaccess"

# SF info
SFFTPHOST="upload.sf.net"
SFFTPDIR="incoming"

REL=$(cat phptelemeter.inc.php | grep define\(\"_version\" | cut -d \" -f 4)

RELNAME="phptelemeter-${REL}"
RELPATH="${TARGET}/${RELNAME}"
RELTAR="${RELPATH}.tar.gz"

VERFILE="${TARGET}/VERSION"

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

if [ -e ${VERFILE} ]; then
        echo -n "${VERFILE} exists, delete? [Y/n] "
        read answer

        case ${answer} in
                [Yy] | '')
                        rm -f ${VERFILE}
                        ;;
                *)
                        echo "${VERFILE} exists, cannot continue."
                        exit 1
                        ;;
        esac
fi

mkdir -p ${RELPATH}

cp -r ${SOURCE}/* ${RELPATH}

mv ${RELPATH}/docs/gpl.txt ${RELPATH}
# clean out
rm -fr ${RELPATH}/*.session ${RELPATH}/*.webprj ${RELPATH}/docs ${RELPATH}/mkrelease.sh ${RELPATH}/patches 
find ${RELPATH} -name "CVS" -exec rm -r {} \;
find ${RELPATH} -name ".svn" -exec rm -r {} \;
find ${RELPATH} -name "*~" -exec rm {} \;

echo ${REL} > ${VERFILE}

#tar it up
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

