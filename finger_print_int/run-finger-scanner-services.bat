@echo off
echo Starting Laravel service and Python app...

:: Start Laravel service in a new window
start "Laravel Service" cmd /k "cd /d %~dp0ZKTeco-Finger-Scanner-feature-no-register && php artisan serve --host 0.0.0.0 --port=8000"

:: Start Python wrapper in a new window
start "Python App" cmd /k "cd /d %~dp0Finger-Scanner-ZKTeco-Wrapper-master && uv run app.py"

echo Both services started in new windows.
