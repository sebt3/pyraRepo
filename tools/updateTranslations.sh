#/bin/sh

for lang in fr-FR it-IT de-DE nl-NL;do

	file=public/langs/${lang}.json
	back="${file}.back"
	[ ! -e $file ] && >$file
	cp $file $back
	{
		if [ $(wc -l < $back) -gt 4 ];then
			cat $back|sed '/}/d'
			X=1
		else
			X=0
			echo '{'
		fi
		{
		grep -r '_(' templates |sed 's/.*{{ _('"'"'//;s/'"'"') }}.*//'|grep -v '}}'
		grep -r '$_(' classes|sed 's/.*\$\_(//;s/).*//;s/^'"'"'//;s/'"'"'$//;s/^"//;s/"$//;s/"/\\\\"/g'
		grep -r 'repo.api.tr(' public/js|sed 's/.*repo.api.tr(//;s/).*//;s/^'"'"'//;s/'"'"'$//;s/^"//;s/"$//'
		}|sort -u|while read line;do
			if ! grep -Fq "\"$line\":" $back;then
				[ $X -ne 0 ] && echo -n ","
				echo "	\"$line\": \"${line}\""
			fi
			X=1
		done
		echo '}'
	} > "$file"
done

