document.addEventListener("DOMContentLoaded", () => {

 // ELEMENTI
 const schermata1 = document.querySelector(".schermata-1");
 const schermata2 = document.querySelector(".schermata-2");
 const schermata3 = document.querySelector(".schermata-3");
 const startBtn = document.getElementById("startBtn");
 const player1 = document.querySelector(".giocatore1");
 const player2 = document.querySelector(".giocatore2");
 const celle = document.querySelectorAll('.cella');
 const roundDisplay = document.getElementById("round");
 const alertBox = document.getElementById("customAlert");
 const alertBtn = document.getElementById("alertBtn");
 const scoreXname = document.getElementById("labelX");
 const scoreOname = document.getElementById("labelO");
 const scoreX = document.getElementById("scoreX");
 const scoreO = document.getElementById("scoreO");

 schermata2.classList.add("hidden");
 schermata3.classList.add("hidden");

 // VARIABILI DI GIOCO
 let contatore = 1;
 let score = { X: 0, O: 0 };
 let currentRound = 1;
 const totalRounds = 3; // quante partite servono
 let alertIsRoundEnd = false;
 let movesHistory = [];
 let roundsHistory = [];

 // -------------------------
 // ALERT CUSTOM
 // -------------------------
 function showAlert(msg) {
 const alertBox = document.getElementById("customAlert");
 const overlay = document.querySelector(".overlay");
 document.getElementById("alertText").innerText = msg;
 alertBox.style.display = "block";
 overlay.style.display = "block";
 }

 document.getElementById("alertBtn").addEventListener("click", () => {
 document.getElementById("customAlert").style.display = "none";
 document.querySelector(".overlay").style.display = "none";
 const punteggio1 = document.querySelector(".punteggio1");
 const punteggio2 = document.querySelector(".punteggio2");

 const finalWinner =
 score["X"] > score["O"]
 ? document.getElementById("player1").value
 : score["O"] > score["X"]
 ? document.getElementById("player2").value
 : "Pareggio";

 if (alertIsRoundEnd) {
 alertIsRoundEnd = false; // reset
 resetBoard();
 if (roundDisplay.textContent != "3" && punteggio1.textContent != 2 && punteggio2.textContent != 2) {
 sorteggia();
 }
 if (currentRound < totalRounds) {
 currentRound++;
 roundDisplay.textContent = currentRound;
 if (punteggio1.textContent == 2 || punteggio2.textContent == 2) {
 const winnerNameDiv = document.getElementById("player-winner");
 const n1 = Number(punteggio1.textContent);
 const n2 = Number(punteggio2.textContent);
 if (n2 < n1) {
 const playerName1 = document.getElementById("player1").value;
 winnerNameDiv.innerHTML = `Ha vinto <span style="color: #C1FF72;">${playerName1}</span>!`;

 } else if (n1 < n2) {
 const playerName2 = document.getElementById("player2").value;
 winnerNameDiv.innerHTML = `Ha vinto <span style="color: #0097B2;">${playerName2}</span>!`;
 }
 endGame(finalWinner);
 }
 return;
 } else {
 endGame(finalWinner);
 }
 }
 })

 // -------------------------
 // START GAME
 // -------------------------
 startBtn.addEventListener("click", () => {
 const inputP1 = document.getElementById("player1").value;
 const inputP2 = document.getElementById("player2").value;

 if (inputP1 == "" || inputP2 == "") {
 showAlert("Scrivi i nomi dei player!"); red
 return
 }

 // AGGIORNA NOMI SCHERMATA 2
 player1.textContent = inputP1;
 player2.textContent = inputP2;

 // AGGIORNA NOMI SCHERMATA 3
 scoreXname.textContent = inputP1;
 scoreOname.textContent = inputP2;

 schermata2.classList.remove("hidden");
 schermata1.classList.add("hidden");

 sorteggia()
 })

 // -------------------------
 // SORTEGGIO
 // -------------------------
 function sorteggia() {
 const inputP1 = document.getElementById("player1").value;
 const inputP2 = document.getElementById("player2").value;

 const sorteggiato = Math.random() < 0.5 ? inputP1 : inputP2;

 alertBox.style.fontSize = "30px"
 alertBtn.style.color = "#313131"

 document.getElementById("turnOf").innerHTML = sorteggiato;

 showAlert("Inizia: " + sorteggiato)

 if (document.getElementById("turnOf").innerHTML == inputP1) {
 document.getElementById("turnOf").style.color = "#C1FF72"
 alertBtn.style.backgroundColor = "#C1FF72"
 alertBox.style.color = "#C1FF72"
 alertBox.style.border = "5px solid #C1FF72"
 contatore = 1;
 } else {
 document.getElementById("turnOf").style.color = "#0097B2"
 alertBtn.style.backgroundColor = "#0097B2"
 alertBox.style.color = "#0097B2"
 alertBox.style.border = "5px solid #0097B2"
 contatore = 0;
 }
 }

 // -------------------------
 // MOSSA DELLA CELLA
 // -------------------------
 celle.forEach((cella, index) => {
 cella.addEventListener('click', () => {
 const inputP1 = document.getElementById("player1").value;
 const inputP2 = document.getElementById("player2").value;
 let row = Math.floor(index / 3) + 1; // riga (1-3)
 let col = (index % 3) + 1; // colonna (1-3)

 // CASO X
 if (cella.textContent === "" && contatore % 2 != 0) {
 document.getElementById("turnOf").innerHTML = inputP2;
 document.getElementById("turnOf").style.color = "#0097B2"
 cella.textContent = "X";
 cella.style.color = '#C1FF72';
 cella.style.border = '3px solid #C1FF72';
 cella.style.backgroundColor = '#545454';

 movesHistory.push({
 round: currentRound,
 player: "X",
 cell: `${row} : ${col}` // salva coordinate
 });
 contatore++;
 checkWin()
 }


 // CASO O
 if (cella.textContent === "" && contatore % 2 == 0) {
 document.getElementById("turnOf").innerHTML = inputP1;
 document.getElementById("turnOf").style.color = "#C1FF72"
 cella.textContent = "O";
 cella.style.color = '#0097B2';
 cella.style.border = '3px solid #0097B2';
 cella.style.backgroundColor = '#545454';

 movesHistory.push({
 round: currentRound,
 player: "O",
 cell: `${row} : ${col}`
 });
 contatore++;
 checkWin()
 }
 });
 });

 // -------------------------
 // CONTROLLO VITTORIA
 // -------------------------
 function checkWin() {
 const board = Array.from(celle).map(c => c.textContent);

 const inputP1 = document.getElementById("player1").value;
 const inputP2 = document.getElementById("player2").value;
 const punteggio1 = document.querySelector(".punteggio1");
 const punteggio2 = document.querySelector(".punteggio2");

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

 if (
 board[a] !== "" &&
 board[a] === board[b] &&
 board[a] === board[c]
 ) {
    const winnerSymbol = board[a];
    const winnerName = winnerSymbol == "X" ? inputP1 : inputP2;

    score[winnerSymbol]++;

    // AGGIORNA PUNTEGGIO SCHERMATA 2
    punteggio1.textContent = score["X"];
    punteggio2.textContent = score["O"];

    // AGGIORNA PUNTEGGIO SCHERMATA 3
    scoreX.textContent = score["X"];
    scoreO.textContent = score["O"];

    if (winnerName == inputP1) {
    alertBtn.style.backgroundColor = "#C1FF72"
    alertBox.style.color = "#C1FF72"
    alertBox.style.border = "5px solid #C1FF72"
    } else {
    alertBtn.style.backgroundColor = "#0097B2"
    alertBox.style.color = "#0097B2"
    alertBox.style.border = "5px solid #0097B2"
    }

    // salva round
    roundsHistory.push({
    round: currentRound,
    winner: winnerName,
    moves: [...movesHistory]
    });

    document.querySelector(".turn-container").style.display = "none"
    showAlert("Hai vinto " + winnerName + "!");
    alertIsRoundEnd = true;

    // Se il vincitore è l'utente loggato
    if(winnerName == document.getElementById("player1").value){

        registraFinePartita("vittoria");

    }else{

        registraFinePartita("sconfitta");

    }

    return
    }
 }

 // PAREGGIO
 if (board.every(c => c !== "")) {

 // salva round
 roundsHistory.push({
 round: currentRound,
 winner: "Pareggio",
 moves: [...movesHistory]
 });

 document.querySelector(".turn-container").style.display = "none"
 showAlert("Pareggio");
 alertIsRoundEnd = true;

 registraFinePartita("pareggio");
 return
 }

 if (punteggio1.textContent == "0" && punteggio2.textContent == "0") {
 scoreX.textContent = "0";
 scoreO.textContent = "0";
 }

 return null;
 }

 // RESET GRIGLIA TRA ROUND
 function resetBoard() {
 document.querySelector(".turn-container").style.display = "flex";
 celle.forEach(cella => {
 cella.textContent = "";
 cella.style.border = '3px solid #C1CBD9';
 cella.style.backgroundColor = '#313131';
 cella.addEventListener('mouseover', () => {
 cella.style.backgroundColor = '#C1CBD9';
 });
 cella.addEventListener('mouseout', () => {
 cella.style.backgroundColor = '#313131';
 });
 });
 movesHistory = [];
 }

 function renderMoves(moves) {
 return moves
 .map((m, i) => {
 const color = m.player === "X" ? "#C1FF72" : "#0097B2"; // verde per X, blu per O
 return `${i + 1}. <span style="color:${color};">${m.player}</span> - ${m.cell}`;
 })
 .join("<br>");
 }


 function endGame() {
 schermata2.classList.add("hidden");
 schermata3.classList.remove("hidden");
 schermata3.style.display = "flex";

 const punteggio1 = document.querySelector(".punteggio1");
 const punteggio2 = document.querySelector(".punteggio2");
 const inputP1 = document.getElementById("player1").value;
 const inputP2 = document.getElementById("player2").value;
 const winnerNameDiv = document.getElementById("player-winner");

 const n1 = Number(punteggio1.textContent);
 const n2 = Number(punteggio2.textContent);

 const round1historyDiv = document.getElementById("round1hystory");
 const round2historyDiv = document.getElementById("round2hystory");
 const round3historyDiv = document.getElementById("round3hystory");

 // Prendi le history salvate
 const r1 = roundsHistory[0];
 const r2 = roundsHistory[1];
 const r3 = roundsHistory[2];

 // Scrivi le mosse nei div
 round1historyDiv.innerHTML = renderMoves(r1.moves);

 round2historyDiv.innerHTML = renderMoves(r2.moves);

 round3historyDiv.innerHTML = renderMoves(r3.moves);

 if (n2 < n1) {
 const playerName1 = document.getElementById("player1").value;
 winnerNameDiv.innerHTML = `Ha vinto <span style="color: #C1FF72;">${playerName1}</span>!`;
 } else if (n1 < n2) {
 const playerName2 = document.getElementById("player2").value;
 winnerNameDiv.innerHTML = `Ha vinto <span style="color: #0097B2;">${playerName2}</span>!`;
 } else {
 winnerNameDiv.textContent = "Pareggio";
 }
 }


 // Funzione per riavviare la partita ricaricando la finestra
 document.querySelector(".btn-ricomincia").addEventListener("click", () => {
 window.location.reload();
 })

 // Funzione da invocare a fine partita
 function registraFinePartita(risultato) {
    // risultato può essere: 'vittoria', 'sconfitta', 'pareggio'
    
    fetch('save_result.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ esito: risultato })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Aggiorna dinamicamente le statistiche nella pagina
            // document.getElementById('vittorie-count').innerText = data.partite_vinte;
            // document.getElementById('sconfitte-count').innerText = data.partite_perse;
            // document.getElementById('pareggi-count').innerText = data.partite_pareggiate;
            console.log("Statistiche aggiornate correttamente.");
        } else {
            alert("Errore nel salvataggio: " + data.message);
        }
    })
    .catch(error => console.error('Errore nella fetch:', error));
}

})