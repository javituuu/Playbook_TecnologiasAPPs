import 'package:material_ui/material_ui.dart';

import '../core/network/api_client.dart';
import '../core/storage/token_storage.dart';
import '../features/auth/data/auth_repository_impl.dart';
import '../features/auth/data/mock_auth_data_source.dart';
import '../features/auth/data/remote_auth_data_source.dart';
import '../features/auth/presentation/auth_controller.dart';
import '../features/auth/presentation/login_screen.dart';
import 'shell/coach_shell.dart';
import 'theme/playbook_theme.dart';

/// Widget raíz de Playbook Coach.
class PlaybookCoachApp extends StatefulWidget {
  const PlaybookCoachApp({
    super.key,
    this.authController,
    this.restoreOnInit = true,
  });

  /// Controlador inyectable para pruebas o configuración personalizada.
  final AuthController? authController;

  /// Si es `true`, intenta restaurar la sesión guardada al iniciar.
  final bool restoreOnInit;

  @override
  State<PlaybookCoachApp> createState() => _PlaybookCoachAppState();
}

class _PlaybookCoachAppState extends State<PlaybookCoachApp> {
  late final AuthController _authController;
  late final bool _ownsController;
  ApiClient? _ownedApiClient;

  @override
  void initState() {
    super.initState();
    if (widget.authController != null) {
      _authController = widget.authController!;
      _ownsController = false;
    } else {
      _ownsController = true;
      _authController = _createDefaultAuthController();
    }

    if (widget.restoreOnInit && _authController.status == AuthStatus.initial) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          _authController.restoreSession();
        }
      });
    }
  }

  AuthController _createDefaultAuthController() {
    final tokenStorage = TokenStorage();
    late final AuthController controller;
    final apiClient = ApiClient(
      tokenStorage: tokenStorage,
      onUnauthorized: () => controller.handleUnauthorized(),
    );
    _ownedApiClient = apiClient;

    final repository = AuthRepositoryImpl(
      tokenStorage: tokenStorage,
      mockDataSource: MockAuthDataSource(),
      remoteDataSource: RemoteAuthDataSource(apiClient: apiClient),
    );

    controller = AuthController(repository: repository);
    return controller;
  }

  @override
  void dispose() {
    if (_ownsController) {
      _authController.dispose();
      _ownedApiClient?.close();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Playbook Coach',
      debugShowCheckedModeBanner: false,
      theme: PlaybookTheme.light(),
      darkTheme: PlaybookTheme.dark(),
      themeMode: ThemeMode.light,
      home: ListenableBuilder(
        listenable: _authController,
        builder: (context, _) {
          return switch (_authController.status) {
            AuthStatus.initial || AuthStatus.restoring => const _SessionSplash(),
            AuthStatus.authenticated => CoachShell(
                controller: _authController,
                apiClient: _ownedApiClient,
              ),
            AuthStatus.unauthenticated ||
            AuthStatus.authenticating =>
              LoginScreen(controller: _authController),
          };
        },
      ),
    );
  }
}

class _SessionSplash extends StatelessWidget {
  const _SessionSplash();

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            CircularProgressIndicator(),
            SizedBox(height: 16),
            Text(
              'Verificando sesión del Coach...',
              style: TextStyle(
                color: PlaybookTheme.dustyGrape,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
