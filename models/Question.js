const mongoose = require('mongoose');

const questionSchema = new mongoose.Schema({
  _id: { type: Number, required: true }, // ID câu hỏi (VD: 301, 302, ...)
  question: { type: String, required: true },
  options: {
    A: { type: String, required: true },
    B: { type: String, required: true },
    C: { type: String, required: true },
    D: { type: String, required: true }
  },
  answer: { type: String, required: true },
  explanation: { type: String, default: '' }
}, {
  versionKey: false
});

module.exports = mongoose.model('Question', questionSchema);