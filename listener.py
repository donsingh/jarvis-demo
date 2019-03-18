import snowboy.snowboydecoder as snow
import os
import pyaudio
import wave

def record_command():
	FORMAT = pyaudio.paInt16
	CHANNELS = 1
	RATE = 44100
	CHUNK = 1024
	RECORD_SECONDS = 2.5
	WAVE_OUTPUT_FILENAME = "audio/command.wav"
	
	audio = pyaudio.PyAudio()

	# start Recording
	stream = audio.open(format=FORMAT, channels=CHANNELS,
					rate=RATE, input=True,
					frames_per_buffer=CHUNK)
	frames = []
	for i in range(0, int(RATE / CHUNK * RECORD_SECONDS)):
		data = stream.read(CHUNK)
		frames.append(data)


	# stop Recording
	stream.stop_stream()
	stream.close()
	audio.terminate()

	waveFile = wave.open(WAVE_OUTPUT_FILENAME, 'wb')
	waveFile.setnchannels(CHANNELS)
	waveFile.setsampwidth(audio.get_sample_size(FORMAT))
	waveFile.setframerate(RATE)
	waveFile.writeframes(b''.join(frames))
	waveFile.close()

def detected_callback():
	os.system("aplay /home/donsingh/temp/demo/audio/sir.wav")
	record_command()

detector = snow.HotwordDetector("/home/donsingh/temp/demo/models/jonel.pmdl", sensitivity=0.5, audio_gain=1)
detector.start(detected_callback)	