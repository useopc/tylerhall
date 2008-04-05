tell application "Mail"
	set theSelectedMessages to selection
	repeat with theMessage in theSelectedMessages
		set theAttachment to first item of theMessage's mail attachments
		set theAttachmentFileName to "Macintosh HD:tmp:" & (theMessage's id as string) & ".ics"
		save theAttachment in theAttachmentFileName
		do shell script "fn='/tmp/$RANDOM.ics';cat " & quoted form of POSIX path of theAttachmentFileName & "| grep -v METHOD:REQUEST > $fn;open $fn; rm " & quoted form of POSIX path of theAttachmentFileName & "; exit 0;"
	end repeat
end tell