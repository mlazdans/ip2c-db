@echo off

rem STEP 0 ############################################
C:\php70\php.exe step0-get-db.php 

IF %ERRORLEVEL% NEQ 0 (
	EXIT %ERRORLEVEL%
)

rem STEP 1 ############################################
C:\php70\php.exe step1-parse-whois.php ^
-w ../whoisdata/afrinic.db ^
-w ../whoisdata/apnic.db.inetnum ^
-w ../whoisdata/arin.db ^
-w ../whoisdata/ripe.db.inetnum ^
-d ../whoisdata/delegated-afrinic-latest ^
-d ../whoisdata/delegated-apnic-latest ^
-d ../whoisdata/delegated-arin-extended-latest ^
-d ../whoisdata/delegated-lacnic-latest ^
-d ../whoisdata/delegated-ripencc-latest

IF %ERRORLEVEL% NEQ 0 (
	EXIT %ERRORLEVEL%
)

rem STEP 2 ############################################
C:\php70\php.exe step2-collect-porcessed.php

IF %ERRORLEVEL% NEQ 0 (
	EXIT %ERRORLEVEL%
)

rem STEP 3 ############################################
C:\php70\php.exe step3-compact-db.php

IF %ERRORLEVEL% NEQ 0 (
	EXIT %ERRORLEVEL%
)

rem STEP 4 ############################################
C:\php70\php.exe step4-combine-all.php

IF %ERRORLEVEL% NEQ 0 (
	EXIT %ERRORLEVEL%
)

rem STEP 5 ############################################
C:\php70\php.exe step5-savebin.php

IF %ERRORLEVEL% NEQ 0 (
	EXIT %ERRORLEVEL%
)
