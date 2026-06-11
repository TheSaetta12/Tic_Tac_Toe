document.addEventListener("DOMContentLoaded", () => {
    // --- 1. ELEMENTI DELL'INTERFACCIA ---
    const schermata1 = document.querySelector(".schermata-1");
    const schermata2 = document.querySelector(".schermata-2");
    const schermata3 = document.querySelector(".schermata-3");
    const startBtn = document.getElementById("startBtn");
    const player1 = document.querySelector(".giocatore1");
    const player2 = document.querySelector(".giocatore2");
    const celle = document.querySelectorAll('.cella');
    const roundDisplay = document.getElementById("round");
    const alertBox = document.getElementById("customAlert");
    const scoreXname = document.getElementById("labelX");
    const scoreOname = document.getElementById("labelO");
    const scoreX = document.getElementById("scoreX");
    const scoreO = document.getElementById("scoreO");

    // Elementi per Registration Hardening
    const regPassword = document.getElementById("reg_password");
    const regSubmit = document.getElementById("reg_submit");

    // --- 2. VALIDAZIONE PASSWORD CON ICONE DINAMICHE ---
    if (regPassword) {
        regPassword.addEventListener("input", () => {
            const val = regPassword.value;
            const criteria = {
                len: val.length >= 8 && val.length <= 12,
                upper: /[A-Z]/.test(val),
                lower: /[a-z]/.test(val),
                num: /\d/.test(val),
                spec: /[@#!$]/.test(val)
            };

            let allValid = true;
            for (const [id, met] of Object.entries(criteria)) {
                const el = document.getElementById(id);
                if (el) {
                    const icon = el.querySelector("i");
                    if (met) {
                        el.classList.replace("invalid", "valid");
                        if (icon) icon.className = "fas fa-circle-check me-2"; // Icona check verde
                    } else {
                        el.classList.replace("valid", "invalid");
                        if (icon) icon.className = "fas fa-circle-xmark me-2"; // Icona X rossa
                        allValid = false;
                    }
                }
            }
            regSubmit.disabled = !allValid; // Abilita solo se tutti i criteri sono ok
        });
    }

    // --- 3. VARIABILI DI GIOCO ---
    let contatore = 1;
    let score = { X: 0, O: 0 };
    let currentRound = 1;
    const totalRounds = 3;
    let alertIsRoundEnd = false;
    let movesHistory = [];
    let roundsHistory = [];

    // Icone FontAwesome per i giocatori
    const iconX = '<i class="fas fa-times"></i>';
    const iconO = '<i class="far fa-circle"></i>';

    // --- 4. GESTIONE ALERT AUTOMATICO ---
    function showAlert(msg) {
        const overlay = document.querySelector(".overlay");
        const alertText = document.getElementById("alertText");

        alertText.innerText = msg;
        alertBox.classList.remove("hidden");
        alertBox.style.display = "block";
        overlay.style.display = "block";

        // Scomparsa automatica dopo 2 secondi e gestione round
        setTimeout(() => {
            alertBox.classList.add("hidden");
            alertBox.style.display = "none";
            overlay.style.display = "none";

            if (alertIsRoundEnd) {
                alertIsRoundEnd = false;
                resetBoard(); // [6]
                if (currentRound < totalRounds && score.X < 2 && score.O < 2) {
                    currentRound++;
                    roundDisplay.textContent = currentRound;
                    sorteggia(); // [7]
                } else {
                    endGame(); // [8]
                }
            }
        }, 2000);
    }

    // --- 5. LOGICA DI AVVIO E SORTEGGIO ---
    startBtn.addEventListener("click", () => {
        const p1 = document.getElementById("player1").value;
        const p2 = document.getElementById("player2").value;
        if (!p1 || !p2) {
            showAlert("Inserire i nomi dei giocatori!");
            return;
        }
        player1.textContent = p1;
        player2.textContent = p2;
        scoreXname.textContent = p1;
        scoreOname.textContent = p2;
        schermata1.classList.add("hidden");
        schermata2.classList.remove("hidden");
        sorteggia();
    });

    function sorteggia() {
        const p1 = document.getElementById("player1").value;
        const p2 = document.getElementById("player2").value;
        const sorteggiato = Math.random() < 0.5 ? p1 : p2;
        document.getElementById("turnOf").innerHTML = sorteggiato;
        document.getElementById("turnOf").style.color = (sorteggiato === p1) ? "#C1FF72" : "#0097B2";
        contatore = (sorteggiato === p1) ? 1 : 0;
        showAlert("Inizia: " + sorteggiato);
    }

    // --- 6. GESTIONE DELLE MOSSE ---
    celle.forEach((cella, index) => {
        cella.addEventListener('click', () => {
            if (cella.innerHTML !== "" || alertIsRoundEnd) return;

            const symbol = (contatore % 2 !== 0) ? "X" : "O";
            const color = (symbol === "X") ? '#C1FF72' : '#0097B2';

            cella.innerHTML = (symbol === "X") ? iconX : iconO; // Inserimento icone
            cella.style.color = color;
            cella.style.border = `3px solid ${color}`;

            movesHistory.push({
                round: currentRound,
                player: symbol,
                cell: `${Math.floor(index / 3) + 1} : ${(index % 3) + 1}`
            });

            contatore++;
            const p1 = document.getElementById("player1").value;
            const p2 = document.getElementById("player2").value;
            document.getElementById("turnOf").innerHTML = (symbol === "X") ? p2 : p1;
            document.getElementById("turnOf").style.color = (symbol === "X") ? "#0097B2" : "#C1FF72";
            checkWin();
        });
    });

    // --- 7. VERIFICA VITTORIA E SALVATAGGIO ---
    function checkWin() {
        // 1. Recupero lo stato attuale della griglia
        const board = Array.from(celle).map(c => {
            if (c.querySelector(".fa-times")) return "X";
            if (c.querySelector(".fa-circle")) return "O";
            return "";
        });

        // 2. COMBINAZIONI VINCENTI CORRETTE
        const winPatterns = [
            [0, 1, 2],
            [3, 4, 5],
            [6, 7, 8],
            [0, 3, 6],
            [1, 4, 7],
            [2, 5, 8],
            [0, 4, 8],
            [2, 4, 6]
        ];

        for (let pattern of winPatterns) {
            const [a, b, c] = pattern;

            // Verifichiamo che la cella 'a' non sia vuota e che a, b, c siano uguali
            if (board[a] !== "" && board[a] === board[b] && board[a] === board[c]) {
                const winnerSymbol = board[a];
                const p1Name = document.getElementById("player1").value;
                const p2Name = document.getElementById("player2").value;
                const winnerName = (winnerSymbol === "X") ? p1Name : p2Name;

                score[winnerSymbol]++;
                aggiornaUI();

                roundsHistory.push({ round: currentRound, winner: winnerName, moves: [...movesHistory] });

                // Salvataggio sicuro nel database
                registraFinePartita(winnerName === p1Name ? "vittoria" : "sconfitta");

                alertIsRoundEnd = true;
                showAlert("Vince il round: " + winnerName);
                return;
            }
        }

        // 3. Controllo pareggio
        if (board.every(c => c !== "")) {
            roundsHistory.push({ round: currentRound, winner: "Pareggio", moves: [...movesHistory] });
            registraFinePartita("pareggio");
            alertIsRoundEnd = true;
            showAlert("Pareggio!");
        }
    }

    function aggiornaUI() {
        document.querySelector(".punteggio1").textContent = score.X;
        document.querySelector(".punteggio2").textContent = score.O;
        scoreX.textContent = score.X;
        scoreO.textContent = score.O;
    }

    function resetBoard() {
        celle.forEach(c => {
            c.innerHTML = "";
            c.style.border = '2px solid #444444';
            c.style.backgroundColor = 'transparent';
        });
        movesHistory = [];
    }

    function registraFinePartita(risultato) {
        fetch('save_result.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ esito: risultato })
        })
            .then(res => res.json())
            .then(data => console.log("Dati sincronizzati col DB.")) // [20]
            .catch(err => console.error('Errore Fetch:', err));
    }

    // --- 8. FINE GIOCO E CRONOLOGIA ---
    function renderMoves(moves) {
        if (!moves) return "Nessuna mossa";
        return moves.map((m, i) => {
            const icon = m.player === "X" ? '<i class="fas fa-times small"></i>' : '<i class="far fa-circle small"></i>';
            const color = m.player === "X" ? "#C1FF72" : "#0097B2";
            return `${i + 1}. <span style="color:${color};">${icon}</span> - ${m.cell}`;
        }).join("<br>");
    }

    function endGame() {
        schermata2.classList.add("hidden");
        schermata3.classList.remove("hidden");
        schermata3.style.display = "flex";

        for (let i = 1; i <= 3; i++) {
            const histDiv = document.getElementById(`round${i}hystory`);
            if (histDiv) histDiv.innerHTML = roundsHistory[i - 1] ? renderMoves(roundsHistory[i - 1].moves) : "Non giocato";
        }

        const winnerNameDiv = document.getElementById("player-winner");
        const p1 = document.getElementById("player1").value;
        const p2 = document.getElementById("player2").value;

        if (score.X > score.O) winnerNameDiv.innerHTML = `Vincitore Finale: <span style="color: #C1FF72;">${p1}</span>`;
        else if (score.O > score.X) winnerNameDiv.innerHTML = `Vincitore Finale: <span style="color: #0097B2;">${p2}</span>`;
        else winnerNameDiv.textContent = "Risultato Finale: Pareggio";
    }

    document.querySelector(".btn-ricomincia").addEventListener("click", () => window.location.reload());
});