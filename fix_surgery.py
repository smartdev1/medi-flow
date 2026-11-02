with open("resources/views/accueil/dashboard.blade.php", "r", encoding="utf-8") as f:
    content = f.read()

old = "this.isSurgeryCategory = code.startsWith('" + "CHIR-" + "');"
new = "this.isSurgeryCategory = code.startsWith('" + "CHIR-" + "') || code.startsWith('" + "BLOC-" + "');"

if old in content:
    content = content.replace(old, new)
    print("Replacement successful!")
else:
    print("Pattern not found")

with open("resources/views/accueil/dashboard.blade.php", "w", encoding="utf-8") as f:
    f.write(content)
print("Done")
