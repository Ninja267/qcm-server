import os

def get_all_files(directory):
    for root, _, files in os.walk(directory):
        for file in files:
            if file != "concatenated.txt":
                yield os.path.join(root, file)

def main():
    folder = input("Enter the path to the folder: ").strip()
    if not os.path.isdir(folder):
        print("Invalid folder path.")
        return

    output_file_path = os.path.join(folder, "concatenated.txt")

    with open(output_file_path, 'w', encoding='utf-8') as outfile:
        for file_path in get_all_files(folder):
            rel_path = os.path.relpath(file_path, folder)
            try:
                with open(file_path, 'r', encoding='utf-8') as infile:
                    content = infile.read()
            except Exception as e:
                print(f"Could not read file {rel_path}: {e}")
                continue

            outfile.write(f"{rel_path}\n")
            outfile.write("\f\f")  # Two form feed characters = page breaks
            outfile.write(content)
            outfile.write("\n" + "-" * 70 + "\n")

    print("Concatenation complete. Output saved to 'concatenated.txt'.")

if __name__ == "__main__":
    main()