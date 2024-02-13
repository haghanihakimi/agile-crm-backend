# Agile App Back-end
<p>
This is the backend for the Agile App project. Agile App is a web application designed to facilitate agile project management by providing tools for task management, organization management, project management, and user management.
</p>

## Features
<ul>
<li>Authentication: Users can register and login securely to access their account.</li>
<li>Task Management: Users can create, edit, and remove tasks for their projects.</li>
<li>Organization Management: Users can create, edit, and delete organizations.</li>
<li>Project Management: Users can create, edit, and delete projects within their organizations.</li>
<li>Member Management: Users can invite, remove, and view members within their organizations.</li>
<li>Profile Management: Users can edit and delete their profile information.</li>
</ul>

## Technologies Used
<ul>
<li>Laravel 10.10 - PHP 8.1</li>
</ul>

## Usage

<ul>
<li>Register/Login: Create a new account or login with existing credentials.</li>
<li>Organization Management: Create, edit, and delete organizations.</li>
<li>Project Management: Create, edit, and delete projects within organizations.</li>
<li>Task Management: Create, edit, and remove tasks for your projects.</li>
<li>Member Management: Invite, remove, and view members within organizations.</li>
<li>Profile Management: Edit and delete your profile information.</li>
</ul>

## Get Started
<p>Ensure that the backend is running on http://127.0.0.1:443 or http://127.0.0.1:8000 and the front-end is running on localhost:3000, and adjust the ports in the instructions below accordingly.</p>
<p>
A proper mailing service like "Mailtrap" is required for this project.
</p>
<ol>
<li>Clone the repository: git clone <code>https://github.com/haghanihakimi/agile-crm-backend</code></li>
<li>Navigate to the project directory: <code>cd agile-app-frontend</code></li>
<li>Install dependencies: <code>composer install</code></li>
<li>Create a <code>.env</code> file and add <code>BACKEND_DOMAIN="http://localhost"</code>
<ul>
<li>Add <code>APP_URL="http://localhost:port_number"</code></li>
<li>Add <code>FRONT_URL="http://localhost:port_number"</code></li>
<li>Set <code>DB_DATABASE=agile</code> and <code>DB_USERNAME=root</code></li>
<li>Set <code>SESSION_DRIVER=cookie</code></li>
<li>Add <code>SESSION_LIFETIME=31536000</code></li>
<li>Add <code>SANCTUM_STATEFUL_DOMAINS=localhost:port_number</code></li>
<li>Add <code>SESSION_DOMAIN=localhost</code></li>
</ul>
</li>
<li>Setup mailtrap account and add info in <code>.env</code> file
<ul>
<li><code>MAIL_MAILER=smtp</code></li>
<li><code>MAIL_HOST=sandbox.smtp.mailtrap.io</code></li>
<li><code>MAIL_PORT=2525</code></li>
<li><code>MAIL_USERNAME=username</code></li>
<li><code>MAIL_PASSWORD=password</code></li>
<li><code>MAIL_ENCRYPTION=tls</code></li>
</ul></li>
<li>Start the development server: <code>php artisan serve</code> or <code>php artisan serve --port=443</code></li>
<li><strong>Make sure you also cloned agile app - front-end repository <code>https://github.com/haghanihakimi/agile-crm-frontend</code></strong></li>
</ul>

## Disclaimer
<p>
This project is provided as-is and is publicly accessible for cloning and usage. By cloning and using this project, you agree that all modifications, installations, and consequences of running this project on your system are solely your responsibility.

The project owner and contributors will not be held responsible for any issues, damages, or losses that may occur as a result of cloning, modifying, or running this project on your system.

Please ensure that you understand the risks and responsibilities involved before using this project.
</p>

## License
GPL-3.0 license
This project is licensed under the <a href="https://github.com/haghanihakimi/agile-crm-frontend?tab=GPL-3.0-1-ov-file#GPL-3.0-1-ov-file">GPL-3 license</a>.