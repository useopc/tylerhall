tell application "Mail"
	set theSelectedMessages to selection
	repeat with theMessage in theSelectedMessages
		set theSource to content of theMessage
		set theFileName to "Macintosh HD:tmp:" & (theMessage's id as string) & ".msg"
		set theFile to open for access file theFileName with write permission
		write theSource to theFile starting at eof
		# close access theFile
		do shell script "curl http://localhost/owa.php?decline=" & POSIX path of theFileName
	end repeat
end tell