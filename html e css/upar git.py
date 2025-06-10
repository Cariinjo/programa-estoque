import pyautogui as pa
import time
import pyperclip

with open('versao.txt', 'r') as f:
    versao = f.read().strip()

major, minor = map(int, versao.split('.'))
minor += 1
nova_versao = f"{major}.{minor}"

with open('versao.txt', 'w') as f:
    f.write(nova_versao)

pa.PAUSE = 2
for tecla in ['`', "'"]:
    pa.hotkey('ctrl', 'shift', tecla)
pa.write("git add .")
pa.press('ENTER')
pa.write(f"git commit -m '{nova_versao}'")
pa.press('ENTER')
time.sleep(4)
pa.write("git push")
pa.press('ENTER')