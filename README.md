CsCrew File Manager

CsCrew File Manager is a PHP-based web file manager with password-protected login, directory navigation, file/folder management, and shell command execution directly from the browser.

Features : 

• Password-Protected Login  
  - Authentication using `password_hash` and `password_verify`.  
  - Default password is `"password"` (hash can be changed in `$correct_password_hash`).  

• Directory Navigation & Display  
  - Breadcrumb navigation.  
  - Sorting by name, size, or date.  

• File & Folder Management  
  - Multiple file uploads.  
  - Create new files or folders.  
  - Rename files or folders.  
  - Delete files or folders (recursive delete for folders).  
  - Change permissions (`chmod`).  
  - Edit file contents directly in the browser.  
  - Download files.  

• Shell Command Execution  
  - Run shell commands directly from the UI.  

• Modern User Interface  
  - Built with Bootstrap 4 and Font Awesome.  
  - SweetAlert for notifications.  
  - Responsive design with dark theme.  

---

Core Functions

| Function                | Description |
|-------------------------|-------------|
| `nhx($str)`             | Decodes a hex string to plain text. |
| `hex($str)`             | Encodes text to a hex string. |
| `perms($f)`             | Gets file/folder permissions in `rwx` format. |
| `a($msg, $sts, $loc)`   | Displays a popup notification and redirects. |
| `deldir($d)`            | Recursively deletes a folder. |
| `path_links($full_path)`| Creates breadcrumb navigation links. |

---

Installation & Usage

1. Upload the PHP file to your server or hosting environment.  
2. Change the default password:  
   - Locate `$correct_password_hash` in the script.  
   - Generate a new password hash using PHP CLI:  
     ```bash
     php -r "echo password_hash('new_password', PASSWORD_DEFAULT);"
     ```
   - Replace the existing hash with your new one.    
3. Log in with your configured password.  
4. Use the features as needed (upload, edit, delete, etc.).  

---

Alert !!!

This script is provided for educational and testing purposes only. The author is not responsible for any misuse.
