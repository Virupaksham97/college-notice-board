require("dotenv").config();
const path = require("path");
const express = require("express");
const cookieParser = require("cookie-parser");
const bcrypt = require("bcryptjs");
const jwt = require("jsonwebtoken");
const pool = require("./lib/db");

const app = express();
app.use(express.json({ limit: "100kb" }));
app.use(cookieParser());

const IS_PROD = process.env.NODE_ENV === "production" || !!process.env.VERCEL;
const SECRET = process.env.JWT_SECRET || "dev-only-secret-change-me";
if (IS_PROD && !process.env.JWT_SECRET) {
  console.warn("WARNING: JWT_SECRET is not set. Set it in your environment.");
}

const CATEGORIES = ["General", "Academic", "Examination", "Events", "Holiday", "Placement", "Sports"];
const COOKIE = "token";

const cookieOptions = () => ({
  httpOnly: true,
  sameSite: "lax",
  secure: IS_PROD,
  maxAge: 8 * 60 * 60 * 1000,
  path: "/",
});

const wrap = (fn) => (req, res, next) => Promise.resolve(fn(req, res, next)).catch(next);

function auth(req, res, next) {
  try {
    req.user = jwt.verify(req.cookies[COOKIE] || "", SECRET);
    next();
  } catch {
    res.status(401).json({ error: "Not logged in." });
  }
}

const requireRole = (...roles) => (req, res, next) =>
  roles.includes(req.user.role) ? next() : res.status(403).json({ error: "Access denied." });

// ---------- Auth ----------
app.post("/api/auth/login", wrap(async (req, res) => {
  const email = String(req.body.email || "").trim();
  const password = String(req.body.password || "");
  if (!email || !password) return res.status(400).json({ error: "Please enter your email and password." });

  const [rows] = await pool.query(
    "SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1", [email]
  );
  const user = rows[0];
  if (!user || !(await bcrypt.compare(password, user.password))) {
    return res.status(401).json({ error: "Invalid email address or password." });
  }
  const payload = { id: user.id, name: user.name, email: user.email, role: user.role };
  res.cookie(COOKIE, jwt.sign(payload, SECRET, { expiresIn: "8h" }), cookieOptions());
  res.json({ user: payload });
}));

app.post("/api/auth/register", wrap(async (req, res) => {
  const name = String(req.body.name || "").trim();
  const email = String(req.body.email || "").trim();
  const password = String(req.body.password || "");
  const confirm = String(req.body.confirm_password || "");

  if (!name || !email || !password || !confirm) return res.status(400).json({ error: "Please fill in all fields." });
  if (name.length > 100) return res.status(400).json({ error: "Name must not exceed 100 characters." });
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) || email.length > 150)
    return res.status(400).json({ error: "Please enter a valid email address." });
  if (password.length < 8) return res.status(400).json({ error: "Password must contain at least 8 characters." });
  if (password !== confirm) return res.status(400).json({ error: "Passwords do not match." });

  const [exists] = await pool.query("SELECT id FROM users WHERE email = ?", [email]);
  if (exists.length) return res.status(409).json({ error: "This email is already registered." });

  const hash = await bcrypt.hash(password, 10);
  // Public registration always creates a student.
  await pool.query("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')", [name, email, hash]);
  res.status(201).json({ ok: true });
}));

app.post("/api/auth/logout", (req, res) => {
  res.clearCookie(COOKIE, { path: "/" });
  res.json({ ok: true });
});

app.get("/api/auth/me", auth, (req, res) => {
  const { id, name, email, role } = req.user;
  res.json({ user: { id, name, email, role } });
});

// ---------- Notices ----------
const VISIBLE = "n.status = 'published' AND (n.expiry_date IS NULL OR n.expiry_date >= CURDATE())";

app.get("/api/stats", auth, wrap(async (req, res) => {
  const [[t]] = await pool.query(
    "SELECT COUNT(*) AS total, COUNT(DISTINCT category) AS cats FROM notices n WHERE " + VISIBLE
  );
  const [recent] = await pool.query(
    `SELECT n.id, n.title, n.category, n.created_at, u.name AS author
     FROM notices n LEFT JOIN users u ON n.admin_id = u.id
     WHERE ${VISIBLE} ORDER BY n.created_at DESC LIMIT 5`
  );
  res.json({ totalNotices: t.total, totalCategories: t.cats, recent });
}));

app.get("/api/notices", auth, wrap(async (req, res) => {
  const search = String(req.query.search || "").trim();
  const category = String(req.query.category || "").trim();
  const isAdmin = req.user.role === "admin";

  let sql = `SELECT n.id, n.title, n.description, n.category, n.status, n.expiry_date,
                    n.created_at, u.name AS author
             FROM notices n LEFT JOIN users u ON n.admin_id = u.id
             WHERE ${isAdmin ? "1=1" : VISIBLE}`;
  const params = [];
  if (search) {
    sql += " AND (n.title LIKE ? OR n.description LIKE ?)";
    params.push(`%${search}%`, `%${search}%`);
  }
  if (CATEGORIES.includes(category)) {
    sql += " AND n.category = ?";
    params.push(category);
  }
  sql += " ORDER BY n.created_at DESC";
  const [rows] = await pool.query(sql, params);
  res.json({ notices: rows });
}));

app.get("/api/notices/:id", auth, requireRole("admin"), wrap(async (req, res) => {
  const id = Number(req.params.id);
  if (!Number.isInteger(id) || id < 1) return res.status(400).json({ error: "Invalid notice ID." });
  const [rows] = await pool.query(
    "SELECT id, title, description, category, status, expiry_date FROM notices WHERE id = ?", [id]
  );
  if (!rows.length) return res.status(404).json({ error: "Notice not found." });
  res.json({ notice: rows[0] });
}));

function validateNotice(body) {
  const title = String(body.title || "").trim();
  const description = String(body.description || "").trim();
  const category = String(body.category || "General");
  const status = String(body.status || "published");
  const expiry = String(body.expiry_date || "").trim();

  if (!title || !description) return { error: "Please enter both the notice title and description." };
  if (title.length > 200) return { error: "The notice title must not exceed 200 characters." };
  if (!CATEGORIES.includes(category)) return { error: "Please select a valid category." };
  if (!["draft", "published"].includes(status)) return { error: "Please select a valid status." };
  if (expiry && !/^\d{4}-\d{2}-\d{2}$/.test(expiry)) return { error: "Invalid expiry date." };
  return { value: { title, description, category, status, expiry: expiry || null } };
}

app.post("/api/notices", auth, requireRole("admin"), wrap(async (req, res) => {
  const { error, value: v } = validateNotice(req.body);
  if (error) return res.status(400).json({ error });
  const [r] = await pool.query(
    "INSERT INTO notices (title, description, category, status, expiry_date, admin_id) VALUES (?, ?, ?, ?, ?, ?)",
    [v.title, v.description, v.category, v.status, v.expiry, req.user.id]
  );
  res.status(201).json({ id: r.insertId });
}));

app.put("/api/notices/:id", auth, requireRole("admin"), wrap(async (req, res) => {
  const id = Number(req.params.id);
  if (!Number.isInteger(id) || id < 1) return res.status(400).json({ error: "Invalid notice ID." });
  const { error, value: v } = validateNotice(req.body);
  if (error) return res.status(400).json({ error });
  const [r] = await pool.query(
    "UPDATE notices SET title=?, description=?, category=?, status=?, expiry_date=? WHERE id=?",
    [v.title, v.description, v.category, v.status, v.expiry, id]
  );
  if (!r.affectedRows) return res.status(404).json({ error: "Notice not found." });
  res.json({ ok: true });
}));

app.delete("/api/notices/:id", auth, requireRole("admin"), wrap(async (req, res) => {
  const id = Number(req.params.id);
  if (!Number.isInteger(id) || id < 1) return res.status(400).json({ error: "Invalid notice ID." });
  const [r] = await pool.query("DELETE FROM notices WHERE id = ?", [id]);
  if (!r.affectedRows) return res.status(404).json({ error: "Notice not found or already deleted." });
  res.json({ ok: true });
}));

// ---------- Misc ----------
app.use("/api", (req, res) => res.status(404).json({ error: "Not found." }));

app.use((err, req, res, next) => {
  console.error(err);
  res.status(500).json({ error: "Server error. Please check the database settings." });
});

// Local development: also serve the frontend.
if (require.main === module) {
  app.use(express.static(path.join(__dirname, "public")));
  const port = process.env.PORT || 3000;
  app.listen(port, () => console.log(`Running at http://localhost:${port}`));
}

module.exports = app;
