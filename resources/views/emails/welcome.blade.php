<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <style>
        /* Reset styles for email clients */
        body, div, p, h1, h2, span {
            margin: 0;
            padding: 0;
        }

        /* Base styles */
        body {
            font-family: 'Share Tech Mono', 'Courier New', monospace;
            background-color: #000000;
            color: white;
            line-height: 1.8;
            font-size: 16px;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        /* Container styles */
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #000000;
            border: 3px solid #39FF14;
            box-shadow: 0 0 30px rgba(57, 255, 20, 0.7);
        }

        /* Header styles */
        .header {
            text-align: center;
            border-bottom: 3px solid #39FF14;
            padding: 40px 20px;
            background: linear-gradient(180deg, rgba(57, 255, 20, 0.2) 0%, rgba(0, 0, 0, 0) 100%);
        }

        .header h1 {
            color: #39FF14;
            font-size: 48px;
            margin: 0;
            padding: 10px;
            text-shadow: 0 0 20px rgba(57, 255, 20, 1);
            letter-spacing: 4px;
            font-weight: bold;
        }

        .header p {
            color: #39FF14;
            font-size: 24px;
            margin-top: 15px;
            text-shadow: 0 0 10px rgba(57, 255, 20, 0.7);
        }

        /* Content styles */
        .content {
            padding: 40px;
            background-color: rgba(57, 255, 20, 0.05);
            font-size: 18px;
            color: #fff !important;
        }

        /* Credentials box styles */
        .credentials-box {
            background-color: #001800;
            padding: 30px;
            margin: 30px 0;
            border: 2px solid #39FF14;
            box-shadow: 0 0 25px rgba(57, 255, 20, 0.5);
            text-align: center;
        }

        .credentials-item {
            margin: 20px 0;
            padding: 15px;
            background-color: rgba(57, 255, 20, 0.1);
            border-left: 5px solid #39FF14;
            font-size: 22px;
        }

        .credentials-label {
            color: #39FF14;
            font-weight: bold;
            display: block;
            margin-bottom: 10px;
            font-size: 20px;
            text-shadow: 0 0 10px rgba(57, 255, 20, 0.7);
        }

        .credentials-value {
            color: #FFFFFF;
            font-size: 26px;
            text-shadow: 0 0 15px rgba(255, 255, 255, 0.7);
            letter-spacing: 2px;
            font-weight: bold;
        }

        /* Warning box styles */
        .warning-box {
            background-color: rgba(255, 0, 0, 0.15);
            border: 2px solid #FF0000;
            color: #FF3333;
            padding: 20px;
            margin: 30px 0;
            text-align: center;
            box-shadow: 0 0 20px rgba(255, 0, 0, 0.4);
            font-size: 20px;
            text-shadow: 0 0 10px rgba(255, 0, 0, 0.4);
        }

        /* Link styles */
        .link {
            color: #39FF14;
            text-decoration: none;
            font-weight: bold;
            border-bottom: 2px solid #39FF14;
            font-size: 22px;
            text-shadow: 0 0 10px rgba(57, 255, 20, 0.7);
        }

        /* Signature styles */
        .signature {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid rgba(57, 255, 20, 0.3);
            font-size: 20px;
            color: #fff !important;
        }

        .team-name {
            color: #39FF14;
            font-weight: bold;
            font-size: 24px;
            text-shadow: 0 0 10px rgba(57, 255, 20, 0.7);
        }

        /* Decorative elements */
        .corner {
            height: 3px;
            width: 100%;
            background: linear-gradient(90deg, transparent, #39FF14, transparent);
            margin: 25px 0;
            box-shadow: 0 0 15px rgba(57, 255, 20, 0.5);
        }

        /* Matrix effect background */
        .matrix-bg {
            position: relative;
            overflow: hidden;
        }

        .matrix-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(0deg, 
                    rgba(57, 255, 20, 0.05) 50%, 
                    rgba(0, 0, 0, 0) 100%);
            pointer-events: none;
        }

        /* Responsive styles */
        @media only screen and (max-width: 800px) {
            .container {
                width: 95% !important;
                margin: 10px !important;
            }
            
            .content {
                padding: 20px !important;
            }
            
            .header h1 {
                font-size: 36px !important;
            }

            .credentials-value {
                font-size: 20px !important;
            }
        }
    </style>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding: 20px;">
                <div class="container matrix-bg">
                    <div class="header">
                        <h1>OSINT MASTERS</h1>
                        <div class="corner"></div>
                        <p>SYSTEM NOTIFICATION</p>
                    </div>
                    <div class="content">
                        <p>Witaj <span style="color: #39FF14; font-weight: bold; font-size: 22px;">{{ $name }}</span>,</p>
                        
                        <p>Administrator właśnie utworzył dla Ciebie konto w systemie OSINT Masters.</p>
                        
                        <div class="credentials-box">
                            <div class="credentials-item">
                                <span class="credentials-label">Email:</span>
                                <span class="credentials-value">{{ $email }}</span>
                            </div>
                            <div class="credentials-item">
                                <span class="credentials-label">Hasło:</span>
                                <span class="credentials-value">{{ $password }}</span>
                            </div>
                        </div>
                        
                        <p>Ze względów bezpieczeństwa, po pierwszym logowaniu będziesz musiał(a) zmienić swoje hasło.</p>
                        
                        <div class="warning-box">
                            <strong>WAŻNE:</strong> To hasło jest tymczasowe i powinno zostać zmienione natychmiast po zalogowaniu!
                        </div>
                        
                        <p>Aby się zalogować, przejdź na stronę: <a href="https://osintmasters.pl" class="link">osintmasters.pl</a></p>
                        
                        <div class="signature">
                            <p>Pozdrawiamy,<br>
                            <span class="team-name">Zespół OSINT Masters</span></p>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>