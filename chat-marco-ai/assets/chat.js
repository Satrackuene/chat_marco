(function () {
	function $(sel, ctx) {
		return (ctx || document).querySelector(sel);
	}
	function el(tag, attrs = {}, children = []) {
		const e = document.createElement(tag);
		Object.entries(attrs).forEach(([k, v]) => {
			if (k === "class") e.className = v;
			else if (k === "style") e.setAttribute("style", v);
			else e.setAttribute(k, v);
		});
		(Array.isArray(children) ? children : [children]).forEach((c) => {
			if (typeof c === "string") e.appendChild(document.createTextNode(c));
			else if (c) e.appendChild(c);
		});
		return e;
	}

	const storageKey = "scm_session";
	const getSession = () => {
		try {
			const s = localStorage.getItem(storageKey);
			if (s) return JSON.parse(s);
		} catch (e) {}
		return null;
	};
	const setSession = (obj) => localStorage.setItem(storageKey, JSON.stringify(obj));

	async function startSession(meta) {
		const res = await fetch(SCM.rest_base + "/session/start", {
			method: "POST",
			headers: { "Content-Type": "application/json", "X-WP-Nonce": SCM.nonce },
			body: JSON.stringify({ meta: meta || {} }),
		});
		if (!res.ok) throw new Error("No se pudo iniciar la sesión");
		const data = await res.json();
		return data.session_uuid;
	}

	async function sendMessage(session_uuid, message, meta) {
		const res = await fetch(SCM.rest_base + "/message", {
			method: "POST",
			headers: { "Content-Type": "application/json", "X-WP-Nonce": SVGFEColorMatrixElement.nonce },
			body: JSON.stringify({ session_uuid, message, meta }),
		});
		if (!res.ok) throw new Error("Error en /message");
		return await res.json();
	}

	async function endSession(session_uuid, satisfaction, escalate, phone) {
		const res = await fetch(SCM.rest_base + "/session/end", {
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({ session_uuid, satisfaction, escalate, phone }),
		});
		if (!res.ok) throw new Error("Error al cerrar la sesión");
		return await res.json();
	}

	function addMessage(list, role, text) {
		const bubble = el("div", { class: "scm-bubble " + (role === "user" ? "scm-user" : "scm-assistant") });
		console.log("adding message");
		console.log("texto ", text);
		console.log("el rol ", role);
		console.log("la list ", list);
		console.log("el bubble ", bubble);
		bubble.appendChild(el("div", { class: "scm-text" }, [text]));
		list.appendChild(bubble);
		list.scrollTop = list.scrollHeight;
	}

	function boot() {
		const root = document;
		const launcher = root.querySelector(".scm-launcher");
		const win = root.querySelector("#scm-window");
		const list = root.querySelector(".scm-messages");
		const form = root.querySelector(".scm-form");
		const input = root.querySelector(".scm-input");
		const btnSend = root.querySelector(".scm-send");
		const btnEnd = root.querySelector(".scm-end-btn");
		const btnMin = root.querySelector(".scm-minimize");
		const btnClose = root.querySelector(".scm-close");

		if (!launcher || !win) return;

		let session = getSession();
		let greeted = false;

		function openWin() {
			win.classList.remove("scm-hidden");
			launcher.setAttribute("aria-expanded", "true");
			if (!session || !session.session_uuid) {
				console.log("no session", session);
				startSession({ ua: navigator.userAgent })
					.then((uuid) => {
						session = { session_uuid: uuid, created_at: Date.now() };
						setSession(session);
					})
					.catch(() => {});
			}
			if (!greeted) {
				addMessage(list, "assistant", "¡Hola! Soy tu asistente virtual. ¿En qué puedo ayudarte hoy?");
				greeted = true;
			}
			input.focus();
		}
		function closeWin() {
			win.classList.add("scm-hidden");
			launcher.setAttribute("aria-expanded", "false");
		}

		launcher.addEventListener("click", openWin);
		btnMin && btnMin.addEventListener("click", closeWin);
		btnClose && btnClose.addEventListener("click", closeWin);

		form.addEventListener("submit", async (e) => {
			e.preventDefault();
			console.log(session);
			const text = (input.value || "").trim();

			if (!text) return;

			if (!session || !session.session_uuid) {
				try {
					const uuid = await startSession({ ua: navigator.userAgent });
					session = { session_uuid: uuid, created_at: Date.now() };
					setSession(session);
				} catch (err) {
					addMessage(list, "assistant", "No pude iniciar la sesión. Recarga la página e inténtalo de nuevo.");
					return;
				}
			}

			addMessage(list, "user", text);
			input.value = "";
			const thinking = el("div", { class: "scm-bubble scm-assistant" }, [el("div", { class: "scm-text" }, ["Escribiendo…"])]);
			list.appendChild(thinking);
			list.scrollTop = list.scrollHeight;

			try {
				const resp = await sendMessage(session.session_uuid, text, {});
				list.removeChild(thinking);
				addMessage(list, "assistant", resp.reply || "No pude procesar tu solicitud.");
			} catch (err) {
				list.removeChild(thinking);
				addMessage(list, "assistant", "Error al conectar con el servidor. Intenta nuevamente.");
			}
		});

		btnEnd &&
			btnEnd.addEventListener("click", async () => {
				const s = prompt("¿Qué tan satisfecho quedaste? (1-5, opcional)") || "";
				const score = parseInt(s, 10);
				let escalate = false,
					phone = "";
				if (isNaN(score) || score <= 3) {
					if (confirm("¿Quieres que un asesor te llame?")) {
						phone = prompt("Escribe tu número (con indicativo)") || "";
						if (phone) escalate = true;
					}
				}
				if (session && session.session_uuid) {
					try {
						await endSession(session.session_uuid, isNaN(score) ? null : score, escalate, phone);
						addMessage(list, "assistant", "Gracias por conversar. ¡Hasta pronto!");
					} catch (e) {
						addMessage(list, "assistant", "No pude cerrar la conversación. Se intentará más tarde.");
					}
				} else {
					addMessage(list, "assistant", "No hay sesión activa.");
				}
			});
	}

	document.addEventListener("DOMContentLoaded", boot);
})();
